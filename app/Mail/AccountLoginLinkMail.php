<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The passwordless "log in to your account" link. Short-lived and single-use.
 */
class AccountLoginLinkMail extends QueuedMailable
{
    public function __construct(
        public Customer $customer,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your G-Force Skydiving account login link');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-login-link',
            with: [
                'name' => $this->customer->name,
                'url' => $this->url,
            ],
        );
    }
}
