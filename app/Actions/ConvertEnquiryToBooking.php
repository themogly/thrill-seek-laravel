<?php

namespace App\Actions;

use App\Enums\EnquiryStatus;
use App\Enums\PaymentPurpose;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ConvertEnquiryToBooking
{
    public function __construct(private readonly SendPaymentReceipt $receipt) {}

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
                'customer_id' => $enquiry->customer_id
                    ?? Customer::resolve($enquiry->email, $enquiry->name, $enquiry->phone)->id,
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

        $this->receipt->handle($payment, $booking);

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
}
