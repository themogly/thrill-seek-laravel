<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\EnquiryMessage;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public EnquiryMessage $message) {}

    public function envelope(): Envelope
    {
        $enquiry = $this->message->enquiry;

        return new Envelope(
            subject: "Re: your enquiry {$enquiry->reference} — G-Force Skydiving",
            replyTo: [new Address(app(GeneralSettings::class)->email, 'G-Force Skydiving')],
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
