<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
