<?php

namespace App\Console\Commands;

use App\Actions\StartTandemCheckout;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Safety net behind the checkout.session.expired webhook: any payment hold
 * that outlives the Stripe session by a wide margin is released so the
 * place can be rebooked.
 */
class ReleaseExpiredBookingHolds extends Command
{
    protected $signature = 'bookings:release-expired-holds';

    protected $description = 'Cancel pending-payment bookings whose checkout window has long expired';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(StartTandemCheckout::HOLD_MINUTES + 15);

        $stale = Booking::where('status', BookingStatus::PendingPayment)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $booking) {
            $booking->update(['status' => BookingStatus::Cancelled]);

            $booking->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Failed]);
        }

        $this->info("Released {$stale->count()} expired hold(s).");

        return self::SUCCESS;
    }
}
