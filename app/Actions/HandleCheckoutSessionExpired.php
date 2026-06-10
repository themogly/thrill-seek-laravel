<?php

namespace App\Actions;

use App\Actions\Concerns\ResolvesCheckoutPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Stripe\Event;

/** An abandoned checkout releases the held place straight away. */
class HandleCheckoutSessionExpired
{
    use ResolvesCheckoutPayment;

    public function handle(Event $event): void
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
}
