<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Payment;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DirectBookingWebhookTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();
    }

    public function test_completed_checkout_confirms_the_held_booking_and_emails_everyone(): void
    {
        [$booking, $payment] = $this->heldBooking('cs_direct_1');

        $this->postWebhook('checkout.session.completed', 'cs_direct_1')->assertNoContent();

        $this->assertSame(BookingStatus::Confirmed, $booking->refresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);

        // Receipt + booking confirmation to the customer, notification to admin.
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($booking->email)
            && str_contains($mail->renderedSubject, 'Payment received'));
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($booking->email)
            && str_contains($mail->renderedSubject, 'confirmed'));
        Mail::assertQueued(PaymentReceivedAdminNotification::class);
    }

    public function test_expired_checkout_releases_the_hold(): void
    {
        [$booking, $payment] = $this->heldBooking('cs_direct_2');
        $slot = $booking->availabilitySlot;
        $this->assertSame(0, $slot->remaining_capacity);

        $this->postWebhook('checkout.session.expired', 'cs_direct_2')->assertNoContent();

        $this->assertSame(BookingStatus::Cancelled, $booking->refresh()->status);
        $this->assertSame(PaymentStatus::Failed, $payment->refresh()->status);
        $this->assertSame(1, $slot->refresh()->remaining_capacity);
        Mail::assertNothingQueued();
    }

    public function test_the_release_command_clears_stale_holds(): void
    {
        [$booking, $payment] = $this->heldBooking('cs_direct_3');
        $booking->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        $fresh = $this->heldBooking('cs_direct_4')[0];

        $this->artisan('bookings:release-expired-holds')
            ->expectsOutputToContain('Released 1 expired hold(s).')
            ->assertSuccessful();

        $this->assertSame(BookingStatus::Cancelled, $booking->refresh()->status);
        $this->assertSame(PaymentStatus::Failed, $payment->refresh()->status);
        $this->assertSame(BookingStatus::PendingPayment, $fresh->refresh()->status);
    }

    public function test_success_page_shows_the_booking_once_paid(): void
    {
        [$booking] = $this->heldBooking('cs_direct_5');
        $this->postWebhook('checkout.session.completed', 'cs_direct_5');

        $response = $this->get('/payment/success?session_id=cs_direct_5');

        $response->assertOk();
        $response->assertSee($booking->reference);
        $response->assertSee('What happens next');
    }

    public function test_success_page_shows_a_processing_state_before_the_webhook_lands(): void
    {
        $this->heldBooking('cs_direct_6');

        $this->get('/payment/success?session_id=cs_direct_6')
            ->assertOk()
            ->assertSee('being confirmed');
    }

    /** @return array{0: Booking, 1: Payment} */
    private function heldBooking(string $sessionId): array
    {
        $slot = AvailabilitySlot::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 1]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PendingPayment,
            'availability_slot_id' => $slot->id,
            'scheduled_at' => $slot->starts_at,
            'price_pence' => 26000,
        ]);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'purpose' => 'tandem_full',
            'amount_pence' => 26000,
            'stripe_checkout_session_id' => $sessionId,
        ]);

        return [$booking, $payment];
    }

    private function postWebhook(string $type, string $sessionId): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_direct_1',
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_direct_123',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', '/webhooks/stripe', content: $payload, server: [
            'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ]);
    }
}
