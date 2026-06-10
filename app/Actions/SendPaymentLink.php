<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EnquiryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeCheckout;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;

class SendPaymentLink
{
    public function __construct(private readonly StripeCheckout $stripe) {}

    /**
     * Create a Stripe Checkout session for the enquiry, email the link to
     * the customer and mark the enquiry as awaiting payment.
     */
    public function handle(Enquiry $enquiry, PaymentPurpose $purpose, int $amountPence, string $description, User $user): Payment
    {
        $payment = Payment::create([
            'enquiry_id' => $enquiry->id,
            'booking_id' => $enquiry->booking?->id,
            'purpose' => $purpose,
            'method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount_pence' => $amountPence,
            'description' => $description,
            'created_by' => $user->id,
        ]);

        $session = $this->stripe->createSession($payment);

        $payment->update(['stripe_checkout_session_id' => $session['id']]);

        Mail::to($enquiry->email)->queue(new TemplatedMail(
            EmailTemplate::findByKey('payment_link'),
            [
                'name' => $enquiry->name,
                'amount' => Money::formatPence($amountPence),
                'description' => $description,
                'link' => $session['url'],
                'reference' => $enquiry->reference,
            ],
        ));

        if (in_array($enquiry->status, [EnquiryStatus::New, EnquiryStatus::Replied], true)) {
            $enquiry->update(['status' => EnquiryStatus::PaymentSent]);
        }

        return $payment;
    }
}
