<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;

class HandleStripeWebhook
{
    public function __construct(
        private readonly ConvertEnquiryToBooking $convertEnquiry,
        private readonly ConfirmHeldBooking $confirmHeldBooking,
    ) {}

    public function handle(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->completed($event),
            'checkout.session.expired' => $this->expired($event),
            default => null,
        };
    }

    private function completed(Event $event): void
    {
        $payment = $this->paymentFor($event);

        // Stripe retries webhooks; a second delivery must not double-convert.
        if ($payment === null || $payment->status === PaymentStatus::Paid) {
            return;
        }

        /** @var Session $session */
        $session = $event->data->object;

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : null,
        ]);

        if ($payment->enquiry_id !== null) {
            $this->convertEnquiry->handle($payment);

            return;
        }

        if ($payment->booking?->status === BookingStatus::PendingPayment) {
            $this->confirmHeldBooking->handle($payment);
        }
    }

    /** An abandoned checkout releases the held place straight away. */
    private function expired(Event $event): void
    {
        $payment = $this->paymentFor($event);

        if ($payment === null || $payment->status !== PaymentStatus::Pending) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Failed]);

        $booking = $payment->booking;

        if ($booking !== null && $booking->status === BookingStatus::PendingPayment) {
            $booking->update(['status' => BookingStatus::Cancelled]);
        }
    }

    private function paymentFor(Event $event): ?Payment
    {
        /** @var Session $session */
        $session = $event->data->object;

        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if ($payment === null) {
            Log::warning('Stripe webhook for unknown checkout session', ['session_id' => $session->id]);
        }

        return $payment;
    }
}
