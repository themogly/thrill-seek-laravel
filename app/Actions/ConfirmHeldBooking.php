<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\Payment;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConfirmHeldBooking
{
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

        $this->sendReceipt($payment, $booking);

        return $booking;
    }

    private function sendReceipt(Payment $payment, Booking $booking): void
    {
        try {
            $booking->refresh();

            Mail::to($booking->email)->queue(new TemplatedMail(
                EmailTemplate::findByKey('payment_received'),
                [
                    'name' => $booking->name,
                    'amount' => $payment->formatted_amount,
                    'product' => $booking->product->name ?? 'your booking',
                    'reference' => $booking->reference,
                    'balance_note' => $booking->hasOutstandingBalance()
                        ? 'Your remaining balance is '.Money::formatPence($booking->balance_due_pence).'.'
                        : 'There is nothing left to pay.',
                ],
            ));

            Mail::to(app(GeneralSettings::class)->email)
                ->queue(new PaymentReceivedAdminNotification($payment, $booking));
        } catch (\Throwable $e) {
            Log::error('Failed to queue direct-booking receipt emails', [
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
