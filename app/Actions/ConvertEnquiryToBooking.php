<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EnquiryStatus;
use App\Enums\PaymentPurpose;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\Payment;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConvertEnquiryToBooking
{
    /**
     * Called when a payment succeeds: attach it to the enquiry's booking
     * (creating the booking if this is the first payment), mark the enquiry
     * converted and send confirmations.
     */
    public function handle(Payment $payment): Booking
    {
        $booking = DB::transaction(function () use ($payment): Booking {
            $enquiry = $payment->enquiry()->lockForUpdate()->firstOrFail();

            $booking = $enquiry->booking()->first() ?? Booking::create([
                'name' => $enquiry->name,
                'email' => $enquiry->email,
                'phone' => $enquiry->phone,
                'product_id' => $enquiry->product_id,
                'enquiry_id' => $enquiry->id,
                'price_pence' => $this->bookingPrice($payment),
                'customer_details' => $enquiry->context,
                'notes' => $enquiry->preferred_date
                    ? 'Preferred date from enquiry: '.$enquiry->preferred_date->format('j M Y')
                    : null,
            ]);

            $payment->update(['booking_id' => $booking->id]);

            if ($enquiry->status !== EnquiryStatus::Closed) {
                $enquiry->update(['status' => EnquiryStatus::Converted]);
            }

            return $booking;
        });

        $this->sendEmails($payment, $booking);

        return $booking;
    }

    /**
     * Deposit/balance payments are instalments against the product's full
     * price; full or custom payments define the booking value themselves.
     */
    private function bookingPrice(Payment $payment): int
    {
        $productPrice = $payment->enquiry?->product?->price_pence;

        return match ($payment->purpose) {
            PaymentPurpose::AffDeposit, PaymentPurpose::AffBalance => $productPrice ?? $payment->amount_pence,
            default => $payment->amount_pence,
        };
    }

    private function sendEmails(Payment $payment, Booking $booking): void
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
            Log::error('Failed to queue payment confirmation emails', [
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
