<?php

namespace App\Actions;

use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\Payment;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Queue the customer receipt + the admin payment notification after a payment
 * succeeds. Returns whether both were queued. Shared by both success paths (enquiry → booking conversion and direct
 * held-booking confirmation) so the wording and recipients live in one place. Mail is
 * queued and wrapped: a mail failure logs instead of breaking the webhook.
 */
class SendPaymentReceipt
{
    public function handle(Payment $payment, Booking $booking): bool
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
            Log::error('Failed to queue payment receipt emails', [
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
