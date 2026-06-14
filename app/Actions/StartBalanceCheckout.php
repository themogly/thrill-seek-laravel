<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\StripeCheckout;
use InvalidArgumentException;

/**
 * Customer-initiated "pay my balance". Reuses the exact admin checkout path: a
 * pending Stripe Payment + StripeCheckout::createSession, resolved by the existing
 * webhook (HandleCheckoutSessionCompleted) which records it Paid and clears the
 * balance. No second payment implementation, no business logic duplicated here.
 */
class StartBalanceCheckout
{
    public function __construct(private readonly StripeCheckout $stripe) {}

    /** @return string the hosted Checkout URL to redirect the customer to */
    public function handle(Booking $booking): string
    {
        $balance = $booking->balance_due_pence;

        if ($balance <= 0) {
            throw new InvalidArgumentException('Booking has no outstanding balance.');
        }

        $purpose = $booking->product?->type === ProductType::Aff
            ? PaymentPurpose::AffBalance
            : PaymentPurpose::Custom;

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'purpose' => $purpose,
            'method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount_pence' => $balance,
            'description' => 'Balance for '.$booking->product->name.' ('.$booking->reference.')',
        ]);

        $session = $this->stripe->createSession($payment);
        $payment->update(['stripe_checkout_session_id' => $session['id']]);

        return $session['url'];
    }
}
