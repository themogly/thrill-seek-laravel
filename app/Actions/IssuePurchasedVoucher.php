<?php

namespace App\Actions;

use App\Mail\VoucherGiftMail;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class IssuePurchasedVoucher
{
    public function __construct(private readonly GenerateVoucherPdf $generatePdf) {}

    /**
     * A voucher purchase has been paid: create the voucher from the
     * payment's metadata and email it to the purchaser. Idempotent — a
     * webhook retry finds the existing voucher and stops.
     */
    public function handle(Payment $payment): Voucher
    {
        $existing = Voucher::where('payment_id', $payment->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $metadata = $payment->metadata ?? [];

        $voucher = Voucher::create([
            'product_id' => $metadata['product_id'] ?? null,
            'amount_pence' => $payment->amount_pence,
            'purchaser_name' => $metadata['purchaser_name'] ?? 'Unknown',
            'purchaser_email' => $metadata['purchaser_email'] ?? '',
            'recipient_name' => $metadata['recipient_name'] ?? null,
            'message' => $metadata['message'] ?? null,
            'expires_at' => now()->addYear()->toDateString(),
            'source' => 'online',
            'payment_id' => $payment->id,
        ]);

        // The printable voucher rides along on the gift email; a PDF failure
        // must never block issuing the voucher itself.
        try {
            $this->generatePdf->handle($voucher);
        } catch (\Throwable $e) {
            Log::error('Failed to generate voucher PDF', [
                'voucher_id' => $voucher->id,
                'exception' => $e->getMessage(),
            ]);
        }

        try {
            if ($voucher->purchaser_email !== '') {
                Mail::to($voucher->purchaser_email)->queue(new VoucherGiftMail($voucher));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to queue voucher gift email', [
                'voucher_id' => $voucher->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return $voucher;
    }
}
