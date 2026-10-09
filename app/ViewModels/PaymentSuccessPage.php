<?php

namespace App\ViewModels;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Voucher;

/**
 * Resolves what the payment-success page shows. Two strategies, in order:
 * the Stripe Checkout session id from the redirect, or — for voucher-covered
 * bookings that never touch Stripe — the unguessable booking reference,
 * which acts as the claim check.
 */
class PaymentSuccessPage
{
    /** @return array{payment: Payment|null, booking: Booking|null, voucher: Voucher|null} */
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

        // A gift-voucher purchase has no booking: its proof is the voucher the webhook
        // issued for THIS payment (IssuePurchasedVoucher sets vouchers.payment_id). Only
        // reachable through the payment's own session id, like the booking path.
        $voucher = $payment !== null && $payment->purpose === PaymentPurpose::VoucherPurchase && $payment->isPaid()
            ? Voucher::with('product')->where('payment_id', $payment->id)->first()
            : null;

        return [
            'payment' => $payment,
            'booking' => $booking,
            'voucher' => $voucher,
        ];
    }
}
