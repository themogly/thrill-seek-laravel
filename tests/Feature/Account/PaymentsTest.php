<?php

namespace Tests\Feature\Account;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    private function bookingWithPayment(Customer $customer, int $amount, string $desc): Booking
    {
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Confirmed,
            'email' => $customer->email,
        ]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => PaymentStatus::Paid,
            'method' => PaymentMethod::Stripe,
            'purpose' => PaymentPurpose::AffDeposit,
            'amount_pence' => $amount,
            'description' => $desc,
            'paid_at' => now()->subDay(),
        ]);

        return $booking;
    }

    public function test_customer_sees_only_their_own_payments(): void
    {
        $customer = Customer::factory()->create();
        $this->bookingWithPayment($customer, 30000, 'My deposit');

        $other = Customer::factory()->create();
        $this->bookingWithPayment($other, 99900, 'Someone else deposit');

        $this->actingAs($customer, 'customer')
            ->get('/account/payments')
            ->assertOk()
            ->assertSee('My deposit')
            ->assertDontSee('Someone else deposit');
    }

    public function test_receipt_pdf_downloads_for_own_booking(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->bookingWithPayment($customer, 30000, 'Deposit');

        $response = $this->actingAs($customer, 'customer')->get('/account/bookings/'.$booking->id.'/receipt');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_receipt_is_not_downloadable_for_another_customers_booking(): void
    {
        $owner = Customer::factory()->create();
        $booking = $this->bookingWithPayment($owner, 30000, 'Deposit');
        $intruder = Customer::factory()->create();

        $this->actingAs($intruder, 'customer')
            ->get('/account/bookings/'.$booking->id.'/receipt')
            ->assertNotFound();
    }
}
