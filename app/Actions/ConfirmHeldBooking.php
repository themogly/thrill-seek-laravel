<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;

class ConfirmHeldBooking
{
    public function __construct(private readonly SendPaymentReceipt $receipt) {}

    /**
     * A direct (public) booking's payment has succeeded: confirm the held
     * place and send the receipt. The booking-confirmed email is queued by
     * BookingObserver on the status transition.
     */
    public function handle(Payment $payment): Booking
    {
        $booking = $payment->booking()->firstOrFail();

        if ($booking->status === BookingStatus::PendingPayment) {
            $booking->update(['status' => BookingStatus::Confirmed]);
        }

        $this->receipt->handle($payment, $booking);

        return $booking;
    }
}
