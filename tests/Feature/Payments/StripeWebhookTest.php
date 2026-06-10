<?php

namespace Tests\Feature\Payments;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Product;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();
    }

    public function test_a_completed_checkout_marks_the_payment_paid_and_creates_a_booking(): void
    {
        $product = Product::where('slug', 'tandem-skydive')->firstOrFail();
        $enquiry = Enquiry::factory()->create(['product_id' => $product->id]);
        $payment = Payment::factory()->create([
            'enquiry_id' => $enquiry->id,
            'purpose' => PaymentPurpose::TandemFull,
            'amount_pence' => 26000,
            'stripe_checkout_session_id' => 'cs_test_hook1',
        ]);

        $this->postSignedWebhook('cs_test_hook1')->assertNoContent();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('pi_test_123', $payment->stripe_payment_intent_id);

        $booking = Booking::sole();
        $this->assertSame($enquiry->id, $booking->enquiry_id);
        $this->assertSame(BookingStatus::PendingDate, $booking->status);
        $this->assertSame(26000, $booking->price_pence);
        $this->assertSame($booking->id, $payment->booking_id);
        $this->assertFalse($booking->hasOutstandingBalance());

        $this->assertSame(EnquiryStatus::Converted, $enquiry->refresh()->status);

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($enquiry->email));
        Mail::assertQueued(PaymentReceivedAdminNotification::class);
    }

    public function test_an_aff_deposit_leaves_the_balance_outstanding(): void
    {
        $product = Product::where('slug', 'aff-course')->firstOrFail();
        $enquiry = Enquiry::factory()->create(['product_id' => $product->id]);
        Payment::factory()->create([
            'enquiry_id' => $enquiry->id,
            'purpose' => PaymentPurpose::AffDeposit,
            'amount_pence' => 30000,
            'stripe_checkout_session_id' => 'cs_test_hook2',
        ]);

        $this->postSignedWebhook('cs_test_hook2');

        $booking = Booking::sole();
        $this->assertSame(175000, $booking->price_pence);
        $this->assertSame(145000, $booking->balance_due_pence);
        $this->assertTrue($booking->hasOutstandingBalance());
    }

    public function test_webhook_retries_do_not_double_convert(): void
    {
        $enquiry = Enquiry::factory()->create();
        Payment::factory()->create([
            'enquiry_id' => $enquiry->id,
            'amount_pence' => 9000,
            'stripe_checkout_session_id' => 'cs_test_hook3',
        ]);

        $this->postSignedWebhook('cs_test_hook3')->assertNoContent();
        $this->postSignedWebhook('cs_test_hook3')->assertNoContent();

        $this->assertSame(1, Booking::count());
    }

    public function test_invalid_signatures_are_rejected(): void
    {
        $payload = $this->payloadFor('cs_test_bad');

        $this->call(
            'POST',
            '/webhooks/stripe',
            content: $payload,
            server: ['HTTP_Stripe-Signature' => 't=123,v1=invalid', 'CONTENT_TYPE' => 'application/json'],
        )->assertBadRequest();
    }

    public function test_unknown_sessions_are_acknowledged_without_side_effects(): void
    {
        $this->postSignedWebhook('cs_test_unknown')->assertNoContent();

        $this->assertSame(0, Booking::count());
    }

    private function payloadFor(string $sessionId): string
    {
        return json_encode([
            'id' => 'evt_test_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_test_123',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function postSignedWebhook(string $sessionId): TestResponse
    {
        $payload = $this->payloadFor($sessionId);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call(
            'POST',
            '/webhooks/stripe',
            content: $payload,
            server: [
                'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
                'CONTENT_TYPE' => 'application/json',
            ],
        );
    }
}
