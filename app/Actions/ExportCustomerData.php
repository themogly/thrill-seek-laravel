<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\Testimonial;
use App\Models\Voucher;

/**
 * Gather everything personal tied to ONE customer for a GDPR subject-access
 * request, as a plain array (downloaded as JSON by the admin). Strictly scoped to
 * the given customer — joins are all by their id or their email.
 */
class ExportCustomerData
{
    /** @return array<string, mixed> */
    public function handle(Customer $customer): array
    {
        $customer->loadMissing([
            'bookings.payments', 'bookings.product',
            'enquiries.messages', 'enquiries.product',
        ]);

        return [
            'exported_at' => now()->toIso8601String(),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'created_at' => $customer->created_at?->toIso8601String(),
                'last_login_at' => $customer->last_login_at?->toIso8601String(),
            ],
            'bookings' => $customer->bookings->map(fn (Booking $b): array => [
                'reference' => $b->reference,
                'product' => $b->product?->name,
                'status' => $b->status->value,
                'scheduled_at' => $b->scheduled_at?->toIso8601String(),
                'price_pence' => $b->price_pence,
                'details' => $b->customer_details, // DOB / weight / medical_notes etc.
                'payments' => $b->payments->map(fn (Payment $p): array => [
                    'amount_pence' => $p->amount_pence,
                    'status' => $p->status->value,
                    'purpose' => $p->purpose->value,
                    'paid_at' => $p->paid_at?->toIso8601String(),
                ])->all(),
            ])->all(),
            'enquiries' => $customer->enquiries->map(fn (Enquiry $e): array => [
                'reference' => $e->reference,
                'product' => $e->product?->name,
                'status' => $e->status->value,
                'context' => $e->context,
                'created_at' => $e->created_at?->toIso8601String(),
                'messages' => $e->messages->map(fn (EnquiryMessage $m): array => [
                    'direction' => $m->direction->value,
                    'body' => $m->body,
                    'created_at' => $m->created_at?->toIso8601String(),
                ])->all(),
            ])->all(),
            'reviews' => $customer->reviews()->get()->map(fn (Testimonial $r): array => [
                'rating' => $r->rating,
                'quote' => $r->quote,
                'approved' => $r->approved,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
            'vouchers_purchased' => Voucher::where('purchaser_email', $customer->email)->get()
                ->map(fn (Voucher $v): array => [
                    'code' => $v->code,
                    'amount_pence' => $v->amount_pence,
                    'status' => $v->status->value,
                    'created_at' => $v->created_at?->toIso8601String(),
                ])->all(),
            'newsletter' => NewsletterSubscriber::where('email', $customer->email)->first()?->only([
                'email', 'consented_at', 'confirmed_at', 'unsubscribed_at',
            ]),
        ];
    }
}
