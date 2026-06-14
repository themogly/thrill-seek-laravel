<?php

namespace Tests\Feature\Security;

use App\Actions\EraseCustomerData;
use App\Actions\ExportCustomerData;
use App\Enums\MessageDirection;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Testimonial;
use Tests\TestCase;

class CustomerDataRightsTest extends TestCase
{
    public function test_export_includes_only_this_customers_data(): void
    {
        $customer = Customer::factory()->create(['name' => 'Jess Jumper']);
        Booking::factory()->create(['customer_id' => $customer->id, 'customer_details' => ['medical_notes' => 'mild asthma']]);

        $other = Customer::factory()->create(['name' => 'Sam Someone-Else']);
        Booking::factory()->create(['customer_id' => $other->id]);

        $data = app(ExportCustomerData::class)->handle($customer);
        $json = json_encode($data);

        $this->assertSame('Jess Jumper', $data['customer']['name']);
        $this->assertCount(1, $data['bookings']);
        $this->assertStringContainsString('mild asthma', $json);          // their own medical detail
        $this->assertStringNotContainsString('Sam Someone-Else', $json);  // never another customer's
    }

    public function test_erase_anonymises_personal_and_medical_data_but_keeps_anonymised_financials(): void
    {
        $customer = Customer::factory()->create(['name' => 'Jess Jumper', 'email' => 'jess@example.com']);
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id, 'name' => 'Jess Jumper', 'email' => 'jess@example.com',
            'price_pence' => 26000,
            'customer_details' => ['medical_notes' => 'mild asthma', 'date_of_birth' => '1990-01-01', 'weight_kg' => '80', 'postcode' => 'EX1 1AA'],
        ]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'status' => PaymentStatus::Paid, 'amount_pence' => 26000]);
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id, 'email' => 'jess@example.com']);
        $enquiry->messages()->create(['direction' => MessageDirection::Inbound, 'body' => 'My number is 07123 456789']);
        Testimonial::factory()->create(['customer_id' => $customer->id]);

        app(EraseCustomerData::class)->handle($customer);

        // Personal/medical erased.
        $customer->refresh();
        $this->assertTrue($customer->isErased());
        $this->assertSame('Erased customer', $customer->name);
        $this->assertStringNotContainsString('jess@example.com', $customer->email);
        $this->assertNull($booking->refresh()->customer_details);       // medical gone
        $this->assertSame('Erased customer', $booking->name);
        $this->assertSame('[erased]', $enquiry->messages()->first()->body);
        $this->assertSame(0, Testimonial::where('customer_id', $customer->id)->count());

        // No identifying detail survives anywhere.
        $this->assertDatabaseMissing('bookings', ['email' => 'jess@example.com']);
        $this->assertDatabaseMissing('enquiries', ['email' => 'jess@example.com']);

        // Anonymised financial records ARE kept.
        $this->assertSame(26000, $booking->price_pence);
        $this->assertSame(26000, $payment->fresh()->amount_pence);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_an_erased_customer_can_no_longer_be_found_by_their_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'gone@example.com']);

        app(EraseCustomerData::class)->handle($customer);

        $this->assertNull(Customer::where('email', 'gone@example.com')->first());
    }
}
