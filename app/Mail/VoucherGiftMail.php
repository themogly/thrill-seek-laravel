<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Voucher;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The gift voucher email — designed as a present, not a receipt: code
 * panel, recipient, personal message and how to redeem.
 */
class VoucherGiftMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Voucher $voucher) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎁 Your G-Force Skydiving gift voucher — '.$this->voucher->code,
            replyTo: [new Address(app(GeneralSettings::class)->email, 'G-Force Skydiving')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.voucher-gift',
            with: [
                'voucher' => $this->voucher->loadMissing('product'),
                'bookingUrl' => route('book.tandem'),
            ],
        );
    }
}
