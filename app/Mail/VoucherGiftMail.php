<?php

namespace App\Mail;

use App\Models\Voucher;
use App\Settings\GeneralSettings;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

/**
 * The gift voucher email — designed as a present, not a receipt: code
 * panel, recipient, personal message and how to redeem.
 */
class VoucherGiftMail extends QueuedMailable
{
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

    /**
     * The printable voucher PDF, when it was generated successfully.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->voucher->pdf_path === null || ! Storage::disk('local')->exists($this->voucher->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->voucher->pdf_path)
                ->as('G-Force-Gift-Voucher-'.$this->voucher->code.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
