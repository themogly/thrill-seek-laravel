<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\TandemDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RescheduleBooking
{
    /**
     * Move a booking to a new slot or ad-hoc date/time and (optionally)
     * email the customer — weather cancellations make this routine. Returns
     * whether the customer email was queued, so the screen never claims a
     * send that didn't happen.
     */
    public function handle(Booking $booking, TandemDate|Carbon $newTime, bool $notifyCustomer = true): bool
    {
        $oldDate = $booking->scheduled_at;

        if ($newTime instanceof TandemDate) {
            $booking->tandem_date_id = $newTime->id;
            $booking->scheduled_at = $newTime->starts_at;
        } else {
            $booking->tandem_date_id = null;
            $booking->scheduled_at = $newTime;
        }

        $booking->status = BookingStatus::Rescheduled;
        $booking->save();

        return $notifyCustomer && $this->emailCustomer($booking, $oldDate);
    }

    private function emailCustomer(Booking $booking, ?Carbon $oldDate): bool
    {
        try {
            Mail::to($booking->email)->queue(new TemplatedMail(
                EmailTemplate::findByKey('booking_rescheduled'),
                [
                    'name' => $booking->name,
                    'reference' => $booking->reference,
                    'product' => $booking->product->name ?? 'your jump',
                    'old_date' => Booking::formatScheduled($oldDate) ?? 'not previously scheduled',
                    'new_date' => $booking->scheduledLabel() ?? 'to be confirmed',
                ],
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to queue reschedule email', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
