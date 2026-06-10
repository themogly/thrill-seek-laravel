<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Concerns\ResolvesCheckoutPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;

class HandleCheckoutSessionCompleted
{
    use ResolvesCheckoutPayment;

    public function __construct(
        private readonly ConvertEnquiryToBooking $convertEnquiry,
        private readonly ConfirmHeldBooking $confirmHeldBooking,
        private readonly IssuePurchasedVoucher $issueVoucher,
        private readonly RedeemVoucher $redeemVoucher,
    ) {}

    public function handle(Event $event): void
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

        if ($payment->purpose === PaymentPurpose::VoucherPurchase) {
            $this->issueVoucher->handle($payment);

            return;
        }

        if ($payment->enquiry_id !== null) {
            $this->convertEnquiry->handle($payment);

            return;
        }

        if ($payment->booking?->status === BookingStatus::PendingPayment) {
            $booking = $this->confirmHeldBooking->handle($payment);

            $this->redeemVoucherFromMetadata($payment, $booking);
        }
    }

    /**
     * A partial-redemption checkout carries the voucher id in metadata; the
     * voucher is only consumed once the card payment succeeds. If someone
     * spent it in the meantime, the booking keeps an outstanding balance
     * for the admin to chase rather than failing the whole payment.
     */
    private function redeemVoucherFromMetadata(Payment $payment, Booking $booking): void
    {
        $voucherId = $payment->metadata['voucher_id'] ?? null;

        if ($voucherId === null) {
            return;
        }

        $voucher = Voucher::find($voucherId);

        if ($voucher === null) {
            return;
        }

        try {
            $this->redeemVoucher->handle($voucher, $booking);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Voucher could not be redeemed at webhook time; balance left outstanding', [
                'voucher_id' => $voucher->id,
                'booking_id' => $booking->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
