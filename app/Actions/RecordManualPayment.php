<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

class RecordManualPayment
{
    public function __construct(private readonly ConvertEnquiryToBooking $convertEnquiry) {}

    /**
     * Record a bank transfer the owner received outside Stripe. Runs the
     * same conversion flow as a successful Stripe payment.
     */
    public function handle(
        Enquiry $enquiry,
        PaymentPurpose $purpose,
        int $amountPence,
        string $reference,
        Carbon $paidAt,
        User $user,
    ): Payment {
        $payment = Payment::create([
            'enquiry_id' => $enquiry->id,
            'booking_id' => $enquiry->booking?->id,
            'purpose' => $purpose,
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Paid,
            'amount_pence' => $amountPence,
            'description' => $purpose->getLabel(),
            'reference' => $reference,
            'paid_at' => $paidAt,
            'created_by' => $user->id,
        ]);

        $this->convertEnquiry->handle($payment);

        return $payment;
    }
}
