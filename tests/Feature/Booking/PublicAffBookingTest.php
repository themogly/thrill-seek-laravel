<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Actions\ConfirmHeldBooking;
use App\Enums\BookingPaymentState;
use App\Enums\BookingStatus;
use App\Enums\PaymentPurpose;
use App\Livewire\BookAff;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Product;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PublicAffBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')
                ->andReturn(['id' => 'cs_test_aff1', 'url' => 'https://checkout.stripe.test/cs_test_aff1'])
                ->byDefault();
        });
    }

    public function test_the_aff_page_lists_upcoming_courses_with_places_left(): void
    {
        $course = $this->course();

        $response = $this->get('/aff');

        $response->assertOk();
        $response->assertSee($course->location->name);
        $response->assertSee('8 places left');
        $response->assertSee('/book/aff?course='.$course->id, false);
    }

    public function test_a_student_can_reserve_a_place_with_a_deposit(): void
    {
        $course = $this->course();

        $component = $this->fillDetails(Livewire::test(BookAff::class)->call('chooseCourse', $course->id))
            ->call('continueToReview')
            ->assertSet('step', 3)
            ->set('terms', true)
            ->call('pay');

        $component->assertRedirect('https://checkout.stripe.test/cs_test_aff1');

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::PendingPayment, $booking->status);
        $this->assertSame($course->id, $booking->course_date_id);
        $this->assertSame(175000, $booking->price_pence);

        $payment = Payment::sole();
        $this->assertSame(PaymentPurpose::AffDeposit, $payment->purpose);
        $this->assertSame(30000, $payment->amount_pence);

        $this->assertSame(7, $course->refresh()->remaining_places);
    }

    public function test_course_price_and_deposit_overrides_flow_through(): void
    {
        $course = $this->course(['price_pence' => 165000, 'deposit_pence' => 25000]);

        $this->fillDetails(Livewire::test(BookAff::class)->call('chooseCourse', $course->id))
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertSame(165000, Booking::sole()->price_pence);
        $this->assertSame(25000, Payment::sole()->amount_pence);
        // Nothing is paid until the webhook lands, so the whole overridden
        // price is still due.
        $this->assertSame(165000, Booking::sole()->balance_due_pence);
    }

    public function test_a_full_course_cannot_be_booked(): void
    {
        $course = $this->course(['capacity' => 1]);
        Booking::factory()->create(['course_date_id' => $course->id]);

        $component = $this->fillDetails(
            Livewire::test(BookAff::class)->set('courseDateId', $course->id)->set('step', 2)
        )
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $component->assertSet('step', 1);
        $this->assertStringContainsString('filled up', $component->get('unavailableMessage'));
        $this->assertSame(1, Booking::count()); // only the pre-existing one
    }

    public function test_preselecting_a_course_via_the_url_skips_to_details(): void
    {
        $course = $this->course();

        Livewire::withQueryParams(['course' => $course->id])
            ->test(BookAff::class)
            ->assertSet('step', 2)
            ->assertSet('courseDateId', $course->id);
    }

    public function test_deposit_payment_leaves_the_balance_outstanding_after_webhook(): void
    {
        $course = $this->course();

        $this->fillDetails(Livewire::test(BookAff::class)->call('chooseCourse', $course->id))
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $payment = Payment::sole();
        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        app(ConfirmHeldBooking::class)->handle($payment);

        $booking = Booking::sole()->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(145000, $booking->balance_due_pence);
        $this->assertSame(BookingPaymentState::DepositPaid, $booking->payment_state);
    }

    /** @param  array<string, mixed>  $overrides */
    private function course(array $overrides = []): CourseDate
    {
        $product = Product::where('slug', 'aff-course')->firstOrFail();

        return CourseDate::factory()->create(array_merge([
            'product_id' => $product->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(4)->toDateString(),
            'location_id' => Location::factory()->create(['name' => 'Seville, Spain'])->id,
            'capacity' => 8,
        ], $overrides));
    }

    /**
     * @param  Testable  $component
     */
    private function fillDetails($component)
    {
        return $component
            ->set('name', 'Alex Student')
            ->set('email', 'student@example.com')
            ->set('phone', '07700900789')
            ->set('date_of_birth', now()->subYears(25)->format('Y-m-d'))
            ->set('weight_kg', '75')
            ->set('emergency_contact_name', 'Sam Parent')
            ->set('emergency_contact_phone', '07700900321');
    }
}
