<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VoucherStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RedeemVoucher
{
    /**
     * Apply a voucher's value to a booking as a paid payment and mark the
     * voucher redeemed.
     */
    public function handle(Voucher $voucher, Booking $booking, ?User $user = null): Voucher
    {
        if (! $voucher->isRedeemable()) {
            throw new InvalidArgumentException(
                "Voucher {$voucher->code} is {$voucher->display_status->getLabel()} and cannot be redeemed."
            );
        }

        return DB::transaction(function () use ($voucher, $booking, $user): Voucher {
            // Atomic claim: only one of two concurrent redemptions can flip
            // the status away from active.
            $claimed = Voucher::query()
                ->whereKey($voucher->id)
                ->where('status', VoucherStatus::Active)
                ->update([
                    'status' => VoucherStatus::Redeemed,
                    'redeemed_at' => now(),
                    'booking_id' => $booking->id,
                ]);

            if ($claimed === 0) {
                throw new InvalidArgumentException(
                    "Voucher {$voucher->code} has already been redeemed."
                );
            }

            $booking->payments()->create([
                'enquiry_id' => $booking->enquiry_id,
                'purpose' => PaymentPurpose::Custom,
                'method' => PaymentMethod::Voucher,
                'status' => PaymentStatus::Paid,
                'amount_pence' => $voucher->amount_pence,
                'description' => "Gift voucher {$voucher->code}",
                'reference' => $voucher->code,
                'paid_at' => now(),
                'created_by' => $user?->id,
            ]);

            return $voucher->refresh();
        });
    }
}
