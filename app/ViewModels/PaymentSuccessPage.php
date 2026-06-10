<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;

/**
 * Resolves what the payment-success page shows. Two strategies, in order:
 * the Stripe Checkout session id from the redirect, or — for voucher-covered
 * bookings that never touch Stripe — the unguessable booking reference,
 * which acts as the claim check.
 */
class PaymentSuccessPage
{
    /** @return array{payment: Payment|null, booking: Booking|null} */
    public function viewData(mixed $sessionId, mixed $reference): array
    {
        $payment = null;
        $booking = null;

        if (is_string($sessionId) && $sessionId !== '') {
            $payment = Payment::with(['booking.product', 'booking.courseDate', 'booking.tandemDate'])
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
            $booking = $payment?->booking;
        } elseif (is_string($reference) && $reference !== '') {
            $booking = Booking::with(['product', 'courseDate', 'tandemDate'])
                ->where('reference', strtoupper($reference))
                ->first();
            $payment = $booking?->payments()->where('status', PaymentStatus::Paid)->latest('id')->first();
        }

        return [
            'payment' => $payment,
            'booking' => $booking,
        ];
    }
}
