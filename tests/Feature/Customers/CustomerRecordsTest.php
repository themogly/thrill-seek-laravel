<?php

namespace Tests\Feature\Customers;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Livewire\ContactForm;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerRecordsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();
    }

    public function test_enquiries_from_the_same_email_share_one_customer_record(): void
    {
        foreach (['First message', 'Second message'] as $message) {
            Livewire::test(ContactForm::class)
                ->set('name', 'Repeat Visitor')
                ->set('email', 'repeat@example.com')
                ->set('message', $message)
                ->call('submit');
        }

        $this->assertSame(1, Customer::count());
        $customer = Customer::sole();
        $this->assertSame(2, $customer->enquiries()->count());
    }

    public function test_email_matching_is_case_insensitive(): void
    {
        Customer::resolve('jane@example.com', 'Jane');
        Customer::resolve('JANE@example.com', 'Jane Again');

        $this->assertSame(1, Customer::count());
    }

    public function test_conversion_links_the_booking_to_the_customer(): void
    {
        $this->actingAs(User::factory()->create());
        $enquiry = Enquiry::factory()->create(['email' => 'student@example.com']);
        $customer = Customer::resolve('student@example.com', $enquiry->name);
        $enquiry->update(['customer_id' => $customer->id]);

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('recordBankTransfer', [
                'purpose' => 'tandem_full',
                'amount_pence' => 260, // pounds
                'reference' => 'REF-1',
                'paid_at' => now()->toDateString(),
            ]);

        $this->assertSame($customer->id, $enquiry->refresh()->booking->customer_id);
        $this->assertSame('£260', $customer->total_spent);
    }

    public function test_booking_and_payment_changes_are_activity_logged(): void
    {
        $booking = Booking::factory()->create();
        $booking->update(['status' => BookingStatus::Confirmed]);

        $payment = Payment::factory()->create(['booking_id' => $booking->id]);
        $payment->update(['status' => PaymentStatus::Paid]);

        $this->assertTrue(
            Activity::where('subject_type', Booking::class)
                ->where('subject_id', $booking->id)
                ->where('event', 'updated')
                ->exists()
        );
        $this->assertTrue(
            Activity::where('subject_type', Payment::class)
                ->where('subject_id', $payment->id)
                ->where('event', 'updated')
                ->exists()
        );
    }

    public function test_admin_can_view_a_customer_with_history(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();
        Booking::factory()->create(['customer_id' => $customer->id]);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertOk()
            ->assertSee($customer->email);
    }
}
