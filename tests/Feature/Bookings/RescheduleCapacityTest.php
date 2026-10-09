<?php

namespace Tests\Feature\Bookings;

use App\Actions\RescheduleBooking;
use App\Actions\StartTandemCheckout;
use App\Enums\BookingStatus;
use App\Exceptions\BookingUnavailableException;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use App\Models\Product;
use App\Models\TandemDate;
use App\Models\User;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prompt 021 (Ben, 9 Oct 2026: it should refuse): rescheduling can't put more
 * people on a tandem date than it has places. RescheduleBooking is the gate,
 * using the same count as the online checkout; the admin's slot list only
 * explains.
 */
class RescheduleCapacityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_rescheduling_into_a_full_slot_is_refused_and_nothing_changes(): void
    {
        $full = $this->slotWith(capacity: 2, booked: 2);
        $booking = Booking::factory()->confirmed()->create(['scheduled_at' => now()->addMonth()]);
        $before = $booking->only(['tandem_date_id', 'scheduled_at', 'status']);

        // Through the screen, the full slot is a disabled option, which Filament refuses outright.
        Livewire::test(ListBookings::class)
            ->callTableAction('reschedule', $booking, ['tandem_date_id' => $full->id, 'notify' => true])
            ->assertHasTableActionErrors(['tandem_date_id']);

        $this->assertEquals($before, $booking->fresh()->only(['tandem_date_id', 'scheduled_at', 'status']));
        $this->assertSame(2, $full->activeBookingsCount());
        Mail::assertNothingQueued();
    }

    public function test_the_action_refuses_even_when_the_slot_list_is_bypassed(): void
    {
        $full = $this->slotWith(capacity: 1, booked: 1);
        $booking = Booking::factory()->confirmed()->create();

        try {
            app(RescheduleBooking::class)->handle($booking, $full, notifyCustomer: true);
            $this->fail('A full slot accepted a rescheduled booking.');
        } catch (BookingUnavailableException $e) {
            $this->assertSame("That date is full (1 of 1 places taken) and this booking needs 1. Pick another date, or raise the date's capacity first.", $e->getMessage());
        }

        $this->assertNull($booking->fresh()->tandem_date_id);
        $this->assertSame(1, $full->activeBookingsCount());
        Mail::assertNothingQueued();
    }

    public function test_a_slot_with_exactly_one_place_left_takes_the_booking(): void
    {
        $slot = $this->slotWith(capacity: 3, booked: 2);
        $booking = Booking::factory()->confirmed()->create();

        $emailed = app(RescheduleBooking::class)->handle($booking, $slot);

        $this->assertTrue($emailed);
        $this->assertSame($slot->id, $booking->fresh()->tandem_date_id);
        $this->assertSame(BookingStatus::Rescheduled, $booking->fresh()->status);
        $this->assertTrue($slot->fresh()->isFull());
    }

    public function test_a_booking_already_on_a_full_slot_can_be_rescheduled_within_it(): void
    {
        $slot = $this->slotWith(capacity: 1, booked: 0);
        $booking = Booking::factory()->confirmed()->create(['tandem_date_id' => $slot->id, 'scheduled_at' => $slot->starts_at]);

        app(RescheduleBooking::class)->handle($booking, $slot, notifyCustomer: false);

        $this->assertSame($slot->id, $booking->fresh()->tandem_date_id);
        $this->assertSame(1, $slot->activeBookingsCount());
    }

    public function test_an_unpaid_checkout_hold_takes_a_place_exactly_as_online(): void
    {
        $slot = $this->slotWith(capacity: 1, booked: 0);
        Booking::factory()->create(['tandem_date_id' => $slot->id, 'status' => BookingStatus::PendingPayment]);

        $this->expectException(BookingUnavailableException::class);

        app(RescheduleBooking::class)->handle(Booking::factory()->confirmed()->create(), $slot);
    }

    public function test_a_cancelled_booking_frees_its_place(): void
    {
        $slot = $this->slotWith(capacity: 1, booked: 0);
        Booking::factory()->create(['tandem_date_id' => $slot->id, 'status' => BookingStatus::Cancelled]);
        $booking = Booking::factory()->confirmed()->create();

        app(RescheduleBooking::class)->handle($booking, $slot, notifyCustomer: false);

        $this->assertSame($slot->id, $booking->fresh()->tandem_date_id);
    }

    public function test_the_ad_hoc_date_path_is_unconstrained(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        app(RescheduleBooking::class)->handle($booking, now()->addWeeks(3)->setTime(10, 0), notifyCustomer: false);

        $this->assertNull($booking->fresh()->tandem_date_id);
        $this->assertSame(BookingStatus::Rescheduled, $booking->fresh()->status);
    }

    public function test_the_slot_list_marks_full_slots_and_disables_them(): void
    {
        $full = $this->slotWith(capacity: 1, booked: 1);
        $open = $this->slotWith(capacity: 4, booked: 1);
        $booking = Booking::factory()->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->mountTableAction('reschedule', $booking)
            ->assertFormFieldExists('tandem_date_id', function (Select $field) use ($full, $open): bool {
                $options = $field->getOptions();

                $this->assertSame($full->starts_at->format('D j M Y, H:i').' — full', $options[$full->id]);
                $this->assertStringEndsWith('(3 of 4 places left)', $options[$open->id]);
                $this->assertTrue($field->isOptionDisabled($full->id, $options[$full->id]));
                $this->assertFalse($field->isOptionDisabled($open->id, $options[$open->id]));

                return true;
            });
    }

    public function test_a_slot_that_fills_after_the_list_was_drawn_is_still_refused(): void
    {
        $slot = $this->slotWith(capacity: 1, booked: 0);
        $booking = Booking::factory()->confirmed()->create();

        $component = Livewire::test(ListBookings::class)
            ->mountTableAction('reschedule', $booking)
            ->setTableActionData(['tandem_date_id' => $slot->id, 'notify' => true]);

        // Someone else takes the last place before staff press Reschedule…
        Booking::factory()->confirmed()->create(['tandem_date_id' => $slot->id]);

        // …the option is now disabled, so it's refused at validation; the action itself refuses
        // in the narrower race after validation (test_the_action_refuses_even_when_the_slot_list_is_bypassed).
        $component->callMountedTableAction()->assertHasTableActionErrors(['tandem_date_id']);
        $this->assertNull($booking->fresh()->tandem_date_id);
        Mail::assertNothingQueued();
    }

    public function test_the_online_checkout_and_the_reschedule_agree_on_the_same_slot(): void
    {
        $this->seed(ProductSeeder::class);
        $this->mock(StripeCheckout::class, fn ($mock) => $mock->shouldReceive('createSession')
            ->andReturn(['id' => 'cs_test_cap', 'url' => 'https://checkout.stripe.test/cs_test_cap']));
        $product = Product::where('slug', 'tandem-skydive')->firstOrFail();
        $slot = $this->slotWith(capacity: 2, booked: 1);

        // One place left: both paths would take it.
        $this->assertTrue($slot->hasPlaceFor(Booking::factory()->confirmed()->make()));
        $this->assertFalse($slot->isFull());

        // The online checkout takes it…
        app(StartTandemCheckout::class)->handle($slot, $product, $this->customer());

        // …and now both refuse.
        $this->assertTrue($slot->fresh()->isFull());
        $this->assertFalse($slot->fresh()->hasPlaceFor(Booking::factory()->confirmed()->make()));

        try {
            app(StartTandemCheckout::class)->handle($slot->fresh(), $product, $this->customer());
            $this->fail('The online checkout overbooked.');
        } catch (BookingUnavailableException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(BookingUnavailableException::class);
        app(RescheduleBooking::class)->handle(Booking::factory()->confirmed()->create(), $slot->fresh());
    }

    private function slotWith(int $capacity, int $booked): TandemDate
    {
        $slot = TandemDate::factory()->create(['capacity' => $capacity, 'starts_at' => now()->addWeeks(2)->setTime(9, 0)]);
        Booking::factory()->count($booked)->confirmed()->create(['tandem_date_id' => $slot->id, 'scheduled_at' => $slot->starts_at]);

        return $slot;
    }

    /** @return array{name: string, email: string, phone: string, date_of_birth: string, weight_kg: string, emergency_contact_name: string, emergency_contact_phone: string} */
    private function customer(): array
    {
        return [
            'name' => 'Sam Online',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '07700900000',
            'date_of_birth' => '1990-01-01',
            'weight_kg' => '80',
            'emergency_contact_name' => 'Pat Carer',
            'emergency_contact_phone' => '07700900001',
        ];
    }
}
