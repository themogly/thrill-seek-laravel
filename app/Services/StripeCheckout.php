<?php

namespace App\Services;

use App\Models\Payment;
use Stripe\StripeClient;

/**
 * Thin wrapper around Stripe Checkout so the rest of the app (and tests)
 * never touch the Stripe SDK directly.
 */
class StripeCheckout
{
    public function __construct(private readonly StripeClient $client) {}

    /**
     * Create a hosted Checkout Session for a pending payment. Direct-booking
     * sessions expire quickly so a held place is released if the customer
     * abandons checkout.
     *
     * @return array{id: string, url: string}
     */
    public function createSession(Payment $payment, ?int $expiresAfterMinutes = null, ?string $cancelUrl = null): array
    {
        $parameters = [
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $payment->amount_pence,
                    'product_data' => [
                        'name' => $payment->description ?? 'G-Force Skydiving',
                    ],
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $payment->enquiry->email ?? $payment->booking->email ?? null,
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
            'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl ?? route('payment.cancelled'),
        ];

        if ($expiresAfterMinutes !== null) {
            // Stripe enforces a 30-minute minimum.
            $parameters['expires_at'] = now()->addMinutes(max(30, $expiresAfterMinutes))->getTimestamp();
        }

        $session = $this->client->checkout->sessions->create($parameters);

        return [
            'id' => $session->id,
            'url' => (string) $session->url,
        ];
    }
}
