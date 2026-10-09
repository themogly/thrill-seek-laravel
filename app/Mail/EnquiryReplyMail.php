<?php

namespace App\Mail;

use App\Models\EnquiryMessage;
use App\Settings\GeneralSettings;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EnquiryReplyMail extends QueuedMailable
{
    public function __construct(public EnquiryMessage $message) {}

    public function envelope(): Envelope
    {
        $enquiry = $this->message->enquiry;

        // Reply to the per-enquiry inbound address so the customer's reply threads
        // straight back into this conversation; fall back to the plain site address
        // when no inbound domain is configured.
        $replyTo = $enquiry->replyToAddress() ?? app(GeneralSettings::class)->email;

        return new Envelope(
            subject: "Re: your enquiry {$enquiry->reference} — G-Force Skydiving",
            replyTo: [new Address($replyTo, 'G-Force Skydiving')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.enquiry-reply',
            with: [
                'enquiry' => $this->message->enquiry,
                'body' => $this->message->body,
            ],
        );
    }
}
