<?php

namespace Tests\Feature\Account;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Services\StripeCheckout;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class BookingsTest extends TestCase
{
    private function affBooking(Customer $customer, int $price = 175000, int $paid = 30000): Booking
    {
        $product = Product::factory()->aff()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(14),
            'price_pence' => $price,
            'email' => $customer->email,
        ]);

        if ($paid > 0) {
            Payment::factory()->create([
                'booking_id' => $booking->id,
                'status' => PaymentStatus::Paid,
                'method' => PaymentMethod::Stripe,
                'purpose' => PaymentPurpose::AffDeposit,
                'amount_pence' => $paid,
                'paid_at' => now()->subDays(3),
            ]);
        }

        return $booking->refresh();
    }

    private function mockStripe(string $sessionId = 'cs_test_balance', string $url = 'https://checkout.stripe.test/cs_test_balance'): void
    {
        $this->mock(StripeCheckout::class, function ($mock) use ($sessionId, $url): void {
            $mock->shouldReceive('createSession')->andReturn(['id' => $sessionId, 'url' => $url]);
        });
    }

    public function test_bookings_list_shows_balance_outstanding(): void
    {
        $customer = Customer::factory()->create();
        $this->affBooking($customer); // £1,750 total, £300 paid → £1,450 due

        $this->actingAs($customer, 'customer')
            ->get('/account/bookings')
            ->assertOk()
            ->assertSee('£1,450');
    }

    public function test_pay_balance_initiates_checkout_for_the_outstanding_amount(): void
    {
        $this->mockStripe();
        $customer = Customer::factory()->create();
        $booking = $this->affBooking($customer);

        $this->actingAs($customer, 'customer')
            ->post('/account/bookings/'.$booking->id.'/pay')
            ->assertRedirect('https://checkout.stripe.test/cs_test_balance');

        $payment = Payment::where('booking_id', $booking->id)->where('status', PaymentStatus::Pending)->sole();
        $this->assertSame(145000, $payment->amount_pence); // the outstanding balance
        $this->assertSame('cs_test_balance', $payment->stripe_checkout_session_id);
        $this->assertSame(PaymentPurpose::AffBalance, $payment->purpose);
    }

    public function test_paid_balance_webhook_clears_the_balance(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        $this->mockStripe('cs_bal_hook', 'https://checkout.stripe.test/cs_bal_hook');
        $customer = Customer::factory()->create();
        $booking = $this->affBooking($customer);

        $this->actingAs($customer, 'customer')->post('/account/bookings/'.$booking->id.'/pay');
        $this->assertTrue($booking->refresh()->hasOutstandingBalance());

        $this->postBalanceWebhook('cs_bal_hook')->assertNoContent();

        $booking->refresh();
        $this->assertFalse($booking->hasOutstandingBalance());
        $this->assertSame(0, $booking->balance_due_pence);
    }

    public function test_fully_paid_booking_shows_no_pay_action(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->affBooking($customer, price: 175000, paid: 175000);

        $this->actingAs($customer, 'customer')
            ->get('/account/bookings/'.$booking->id)
            ->assertOk()
            ->assertSee('Paid in full')
            ->assertDontSee('Pay '); // no "Pay … by card" button
    }

    public function test_a_customer_cannot_view_another_customers_booking(): void
    {
        $owner = Customer::factory()->create();
        $intruder = Customer::factory()->create();
        $booking = $this->affBooking($owner);

        $this->actingAs($intruder, 'customer')
            ->get('/account/bookings/'.$booking->id)
            ->assertNotFound();
    }

    public function test_a_customer_cannot_pay_another_customers_booking(): void
    {
        $this->mockStripe();
        $owner = Customer::factory()->create();
        $intruder = Customer::factory()->create();
        $booking = $this->affBooking($owner);

        $this->actingAs($intruder, 'customer')
            ->post('/account/bookings/'.$booking->id.'/pay')
            ->assertNotFound();

        $this->assertSame(0, Payment::where('booking_id', $booking->id)->where('status', PaymentStatus::Pending)->count());
    }

    private function postBalanceWebhook(string $sessionId): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_bal_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => $sessionId, 'object' => 'checkout.session', 'payment_intent' => 'pi_bal_1']],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test_secret');

        return $this->call('POST', '/webhooks/stripe', content: $payload, server: [
            'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
