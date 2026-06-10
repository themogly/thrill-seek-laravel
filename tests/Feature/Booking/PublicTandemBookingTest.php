<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Livewire\BookTandem;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TandemDate;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PublicTandemBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')
                ->andReturn(['id' => 'cs_test_book1', 'url' => 'https://checkout.stripe.test/cs_test_book1'])
                ->byDefault();
        });
    }

    public function test_the_booking_page_renders_open_slots(): void
    {
        $slot = TandemDate::factory()->create([
            'starts_at' => now()->addWeeks(2)->setTime(9, 0),
            'capacity' => 4,
        ]);
        TandemDate::factory()->create(['starts_at' => now()->subWeek()]); // past — hidden

        $response = $this->get('/book/tandem');

        $response->assertOk();
        $response->assertSee($slot->starts_at->format('D j M'));
        $response->assertSee('4 places left');
    }

    public function test_a_customer_can_book_and_is_sent_to_stripe(): void
    {
        $slot = TandemDate::factory()->create([
            'starts_at' => now()->addWeeks(2)->setTime(9, 0),
            'capacity' => 4,
        ]);

        $component = $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->call('continueToReview')
            ->assertSet('step', 3)
            ->set('terms', true)
            ->call('pay');

        $component->assertRedirect('https://checkout.stripe.test/cs_test_book1');

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::PendingPayment, $booking->status);
        $this->assertSame($slot->id, $booking->tandem_date_id);
        $this->assertTrue($booking->scheduled_at->equalTo($slot->starts_at));
        $this->assertSame(26000, $booking->price_pence);
        $this->assertSame('80', $booking->customer_details['weight_kg']);
        $this->assertSame('Pat Carer', $booking->customer_details['emergency_contact_name']);

        $payment = Payment::sole();
        $this->assertSame('cs_test_book1', $payment->stripe_checkout_session_id);
        $this->assertSame(PaymentStatus::Pending, $payment->status);

        $this->assertSame(1, Customer::where('email', 'jumper@example.com')->count());

        // The hold occupies a place immediately.
        $this->assertSame(3, $slot->refresh()->remaining_capacity);
    }

    public function test_purchasable_add_ons_are_priced_into_the_total(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 4]);
        $product = Product::where('slug', 'tandem-skydive')->firstOrFail();
        $camera = $product->addOns()->where('name', 'Outside Camera')->firstOrFail();

        $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->set('addOnIds', [$camera->id])
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertSame(40000, Booking::sole()->price_pence); // £260 + £140
        $this->assertSame(40000, Payment::sole()->amount_pence);
        $this->assertStringContainsString('Outside Camera', Booking::sole()->customer_details['add_ons']);
    }

    public function test_non_purchasable_fees_cannot_be_selected_as_add_ons(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 4]);
        $product = Product::where('slug', 'tandem-skydive')->firstOrFail();
        $insurance = $product->addOns()->where('name', 'P6 Third Party Insurance')->firstOrFail();

        $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->set('addOnIds', [$insurance->id])
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertSame(26000, Booking::sole()->price_pence);
    }

    public function test_capacity_is_enforced_when_two_customers_race_for_the_last_place(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 1]);

        $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertSame(1, Booking::count());

        // Second customer chose the slot while it was still free, but pays
        // after the first hold landed — the lock re-checks and refuses.
        $second = $this->fillDetails(
            Livewire::test(BookTandem::class)->set('slotId', $slot->id)->set('step', 2),
            email: 'second@example.com',
        )
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $second->assertSet('step', 1);
        $this->assertStringContainsString('filled up', $second->get('unavailableMessage'));
        $this->assertSame(1, Booking::count());
    }

    public function test_under_18s_are_rejected(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 4]);

        $this->fillDetails(
            Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id),
            dob: now()->subYears(17)->format('Y-m-d'),
        )
            ->call('continueToReview')
            ->assertHasErrors('date_of_birth')
            ->assertSet('step', 2);
    }

    public function test_stripe_failure_releases_the_hold_and_shows_a_friendly_message(): void
    {
        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')->andThrow(new \RuntimeException('No API key provided.'));
        });

        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 4]);

        $component = $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertStringContainsString('temporarily unavailable', $component->get('paymentErrorMessage'));
        $this->assertSame(BookingStatus::Cancelled, Booking::sole()->status);
        $this->assertSame(PaymentStatus::Failed, Payment::sole()->status);
        $this->assertSame(4, $slot->refresh()->remaining_capacity);
    }

    public function test_honeypot_blocks_bots_without_creating_anything(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 4]);

        $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->set('website', 'https://spam.example')
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertSame(0, Booking::count());
    }

    /**
     * @param  Testable  $component
     */
    private function fillDetails($component, string $email = 'jumper@example.com', ?string $dob = null)
    {
        return $component
            ->set('name', 'Jess Jumper')
            ->set('email', $email)
            ->set('phone', '07700900123')
            ->set('date_of_birth', $dob ?? now()->subYears(28)->format('Y-m-d'))
            ->set('weight_kg', '80')
            ->set('emergency_contact_name', 'Pat Carer')
            ->set('emergency_contact_phone', '07700900456');
    }
}
