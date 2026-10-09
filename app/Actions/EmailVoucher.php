<?php

namespace App\Actions;

use App\Mail\VoucherGiftMail;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email the gift voucher (code + printable PDF) to its purchaser. The one place
 * this mail is sent from — the purchase webhook and the admin "Email voucher"
 * button both call it. Wrapped so a mail failure logs instead of breaking the
 * webhook; returns whether it was queued so a screen never claims a send that
 * didn't happen.
 */
class EmailVoucher
{
    public function handle(Voucher $voucher): bool
    {
        if ($voucher->purchaser_email === '') {
            return false;
        }

        try {
            Mail::to($voucher->purchaser_email)->queue(new VoucherGiftMail($voucher));
        } catch (\Throwable $e) {
            Log::error('Failed to queue voucher gift email', [
                'voucher_id' => $voucher->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
