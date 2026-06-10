<?php

namespace App\Actions;

use App\Enums\MessageDirection;
use App\Mail\EnquiryAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Enquiry;
use App\Models\Product;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateEnquiry
{
    /**
     * Store an enquiry with its opening message, then notify the admin and
     * auto-acknowledge the customer.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone?: string|null,
     *     message?: string|null,
     *     preferred_date?: string|null,
     *     context?: array<string, mixed>|null,
     * }  $data
     */
    public function handle(array $data, ?Product $product = null): Enquiry
    {
        $enquiry = DB::transaction(function () use ($data, $product): Enquiry {
            $customer = Customer::resolve($data['email'], $data['name'], $data['phone'] ?? null);

            $enquiry = Enquiry::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'product_id' => $product?->id,
                'customer_id' => $customer->id,
                'preferred_date' => $data['preferred_date'] ?? null,
                'context' => $data['context'] ?? null,
            ]);

            $enquiry->messages()->create([
                'direction' => MessageDirection::Inbound,
                'body' => filled($data['message'] ?? null)
                    ? $data['message']
                    : '(No message — details submitted via the booking form.)',
            ]);

            return $enquiry;
        });

        $this->sendEmails($enquiry);

        return $enquiry;
    }

    private function sendEmails(Enquiry $enquiry): void
    {
        try {
            $settings = app(GeneralSettings::class);

            Mail::to($settings->email)->queue(new EnquiryAdminNotification($enquiry));

            Mail::to($enquiry->email)->queue(new TemplatedMail(
                EmailTemplate::findByKey('enquiry_acknowledgement'),
                [
                    'name' => $enquiry->name,
                    'reference' => $enquiry->reference,
                    'product' => $enquiry->product->name ?? 'General enquiry',
                ],
            ));
        } catch (\Throwable $e) {
            // Never lose the enquiry because email delivery is misconfigured
            // (e.g. missing RESEND_API_KEY) — it still lands in the admin inbox.
            Log::error('Failed to queue enquiry emails', [
                'enquiry_id' => $enquiry->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
