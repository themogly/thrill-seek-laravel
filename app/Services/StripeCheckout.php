<?php

declare(strict_types=1);

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
     * Create a hosted Checkout Session for a pending payment.
     *
     * @return array{id: string, url: string}
     */
    public function createSession(Payment $payment): array
    {
        $session = $this->client->checkout->sessions->create([
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
            'customer_email' => $payment->enquiry?->email,
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
            'success_url' => route('payment.success'),
            'cancel_url' => route('payment.cancelled'),
        ]);

        return [
            'id' => $session->id,
            'url' => (string) $session->url,
        ];
    }
}
