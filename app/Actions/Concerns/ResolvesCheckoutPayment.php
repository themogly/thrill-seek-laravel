<?php

namespace App\Actions\Concerns;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;

trait ResolvesCheckoutPayment
{
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
