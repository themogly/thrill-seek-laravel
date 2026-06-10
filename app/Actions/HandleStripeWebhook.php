<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;

class HandleStripeWebhook
{
    public function __construct(private readonly ConvertEnquiryToBooking $convertEnquiry) {}

    public function handle(Event $event): void
    {
        if ($event->type !== 'checkout.session.completed') {
            return;
        }

        /** @var Session $session */
        $session = $event->data->object;

        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if ($payment === null) {
            Log::warning('Stripe webhook for unknown checkout session', ['session_id' => $session->id]);

            return;
        }

        // Stripe retries webhooks; a second delivery must not double-convert.
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : null,
        ]);

        if ($payment->enquiry_id !== null) {
            $this->convertEnquiry->handle($payment);
        }
    }
}
