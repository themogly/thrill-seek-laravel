<?php

namespace App\Actions;

use App\Enums\EnquiryStatus;
use App\Enums\MessageDirection;
use App\Mail\EnquiryAdminNotification;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;

/**
 * A reply the customer posts from their account. Threads through the SAME
 * EnquiryMessage system as inbound email — stored inbound, the enquiry flipped to
 * "customer replied" and re-marked unread so it surfaces in the admin inbox (nav
 * badge + "Needs reply"). The admin is also emailed a notification.
 */
class RecordCustomerEnquiryReply
{
    public function handle(Enquiry $enquiry, string $body): EnquiryMessage
    {
        $message = $enquiry->messages()->create([
            'direction' => MessageDirection::Inbound,
            'body' => $body,
            'sender_email' => $enquiry->email,
            'received_at' => now(),
        ]);

        $enquiry->forceFill([
            'status' => EnquiryStatus::CustomerReplied,
            'last_customer_message_at' => now(),
            'read_at' => null,
        ])->save();

        $adminEmail = app(GeneralSettings::class)->email;
        if ($adminEmail !== '') {
            Mail::to($adminEmail)->queue(new EnquiryAdminNotification($enquiry));
        }

        return $message;
    }
}
