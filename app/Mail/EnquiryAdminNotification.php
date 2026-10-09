<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EnquiryAdminNotification extends QueuedMailable
{
    public function __construct(public Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New enquiry {$this->enquiry->reference} from {$this->enquiry->name}",
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.enquiry-admin-notification',
            with: [
                'enquiry' => $this->enquiry->loadMissing(['product', 'messages']),
            ],
        );
    }
}
