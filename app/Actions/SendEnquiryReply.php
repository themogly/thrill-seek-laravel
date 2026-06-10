<?php

namespace App\Actions;

use App\Enums\EnquiryStatus;
use App\Enums\MessageDirection;
use App\Mail\EnquiryReplyMail;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendEnquiryReply
{
    /**
     * Store an outbound reply on the thread, email it to the customer and
     * move the enquiry to "replied" (unless it has already progressed).
     */
    public function handle(Enquiry $enquiry, string $body, User $user): EnquiryMessage
    {
        $message = $enquiry->messages()->create([
            'direction' => MessageDirection::Outbound,
            'body' => $body,
            'user_id' => $user->id,
        ]);

        Mail::to($enquiry->email)->queue(new EnquiryReplyMail($message));

        if ($enquiry->status === EnquiryStatus::New) {
            $enquiry->update(['status' => EnquiryStatus::Replied]);
        }

        return $message;
    }
}
