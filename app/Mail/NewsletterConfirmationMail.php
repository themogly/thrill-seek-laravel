<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

/**
 * Double opt-in: asks the subscriber to confirm before we ever send them a
 * newsletter. The confirm link is a signed route — no login, tamper-proof.
 */
class NewsletterConfirmationMail extends QueuedMailable
{
    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your G-Force newsletter subscription');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter-confirmation',
            with: [
                'confirmUrl' => URL::signedRoute('newsletter.confirm', ['subscriber' => $this->subscriber->getKey()]),
            ],
        );
    }
}
