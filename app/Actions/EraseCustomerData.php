<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * Irreversibly erase a customer's personal + medical data for a GDPR erasure
 * request, while keeping anonymised financial/audit records (booking
 * references, amounts, dates, payments) for accounting. Personal detail —
 * names, contact, address, DOB, weight, height, sex, medical_notes, message
 * bodies — is stripped in place; nothing identifying remains.
 */
class EraseCustomerData
{
    public function handle(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $anonEmail = 'erased-'.$customer->id.'@erased.invalid';

            // Bookings: keep the financial shell, strip name/contact + the medical
            // customer_details JSON entirely.
            foreach ($customer->bookings()->get() as $booking) {
                $booking->forceFill([
                    'name' => 'Erased customer',
                    'email' => $anonEmail,
                    'phone' => null,
                    'customer_details' => null,
                    'notes' => null,
                ])->saveQuietly();
            }

            // Enquiries + their messages: strip contact, the context JSON and bodies.
            foreach ($customer->enquiries()->with('messages')->get() as $enquiry) {
                $enquiry->forceFill([
                    'name' => 'Erased customer',
                    'email' => $anonEmail,
                    'phone' => null,
                    'context' => null,
                ])->saveQuietly();

                foreach ($enquiry->messages as $message) {
                    $message->forceFill([
                        'body' => '[erased]',
                        'raw_body' => null,
                        'sender_email' => null,
                    ])->saveQuietly();
                }
            }

            // Vouchers they bought: anonymise the purchaser (keep code/amount for finance).
            Voucher::where('purchaser_email', $customer->email)->each(function (Voucher $voucher) use ($anonEmail): void {
                $voucher->forceFill(['purchaser_name' => 'Erased customer', 'purchaser_email' => $anonEmail])->saveQuietly();
            });

            // Reviews and newsletter subscription are not financial records — remove them.
            $customer->reviews()->delete();
            NewsletterSubscriber::where('email', $customer->email)->delete();

            // Auth tokens.
            $customer->loginLinks()->delete();

            // Finally anonymise the customer row itself and mark it erased.
            $customer->forceFill([
                'name' => 'Erased customer',
                'email' => $anonEmail,
                'phone' => null,
                'last_login_at' => null,
                'erased_at' => now(),
            ])->save();
        });
    }
}
