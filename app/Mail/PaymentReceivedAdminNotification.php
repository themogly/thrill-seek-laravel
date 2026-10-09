<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReceivedAdminNotification extends QueuedMailable
{
    public function __construct(
        public Payment $payment,
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment received: {$this->payment->formatted_amount} from {$this->booking->name} ({$this->booking->reference})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payment-received-admin',
            with: [
                'payment' => $this->payment,
                'booking' => $this->booking->loadMissing('product'),
            ],
        );
    }
}
