<?php

namespace Tests\Feature\Booking;

use App\Livewire\BookAff;
use App\Livewire\BookTandem;
use App\Livewire\BuyVoucher;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Enquiry;
use App\Models\Location;
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

/**
 * With online payments off the public site is enquiry-first: the booking
 * flows capture the request as an enquiry and never create a Stripe session.
 */
class EnquiryFirstModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $this->setFeature('online_payments_enabled', false);

        // If anything tried to reach Stripe it would be a hard failure.
        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')->never();
        });
    }

    public function test_tandem_booking_creates_an_enquiry_not_a_stripe_session(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2)->setTime(9, 0), 'capacity' => 4]);

        $component = $this->fillTandem(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $component->assertNoRedirect()->assertSet('enquirySent', true);

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Payment::count());

        $enquiry = Enquiry::sole();
        $this->assertSame('jumper@example.com', $enquiry->email);
        $this->assertTrue($enquiry->preferred_date->isSameDay($slot->starts_at));
        $this->assertSame('80', $enquiry->context['weight_kg']);
    }

    public function test_aff_booking_creates_an_enquiry_not_a_stripe_session(): void
    {
        $product = Product::where('slug', 'aff-course')->firstOrFail();
        $course = CourseDate::factory()->create([
            'product_id' => $product->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(4)->toDateString(),
            'location_id' => Location::factory()->create(['name' => 'Seville, Spain'])->id,
            'capacity' => 8,
        ]);

        $component = $this->fillAff(Livewire::test(BookAff::class)->call('chooseCourse', $course->id))
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $component->assertNoRedirect()->assertSet('enquirySent', true);

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Payment::count());

        $enquiry = Enquiry::sole();
        $this->assertSame('student@example.com', $enquiry->email);
        $this->assertSame('Seville, Spain', $enquiry->context['location']);
    }

    public function test_voucher_purchase_creates_an_enquiry_not_a_stripe_session(): void
    {
        $component = Livewire::test(BuyVoucher::class)
            ->set('purchaser_name', 'Gifty McGift')
            ->set('purchaser_email', 'gifter@example.com')
            ->set('recipient_name', 'Lucky Friend')
            ->set('terms', true)
            ->call('pay');

        $component->assertNoRedirect()->assertSet('enquirySent', true);

        $enquiry = Enquiry::sole();
        $this->assertSame('gifter@example.com', $enquiry->email);
        $this->assertSame('Lucky Friend', $enquiry->context['recipient_name']);
    }

    /** @param Testable $component */
    private function fillTandem($component)
    {
        return $component
            ->set('name', 'Jess Jumper')
            ->set('email', 'jumper@example.com')
            ->set('phone', '07700900123')
            ->set('date_of_birth', now()->subYears(28)->format('Y-m-d'))
            ->set('weight_kg', '80')
            ->set('emergency_contact_name', 'Pat Carer')
            ->set('emergency_contact_phone', '07700900456');
    }

    /** @param Testable $component */
    private function fillAff($component)
    {
        return $component
            ->set('name', 'Sam Student')
            ->set('email', 'student@example.com')
            ->set('phone', '07700900123')
            ->set('date_of_birth', now()->subYears(28)->format('Y-m-d'))
            ->set('weight_kg', '80')
            ->set('emergency_contact_name', 'Pat Carer')
            ->set('emergency_contact_phone', '07700900456');
    }
}
