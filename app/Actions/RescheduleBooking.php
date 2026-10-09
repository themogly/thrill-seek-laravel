<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingUnavailableException;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\TandemDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RescheduleBooking
{
    /**
     * Move a booking to a new slot or ad-hoc date/time and (optionally)
     * email the customer — weather cancellations make this routine. Returns
     * whether the customer email was queued, so the screen never claims a
     * send that didn't happen.
     *
     * A move onto a slot is refused when the slot has no place for it, checked
     * under the same lock and the same count as the online checkout, so two
     * staff (or staff and a customer) can't overbook it between them. The
     * ad-hoc date/time path has no slot and so no capacity: deliberately
     * unconstrained.
     *
     * @throws BookingUnavailableException when the slot is full
     */
    public function handle(Booking $booking, TandemDate|Carbon $newTime, bool $notifyCustomer = true): bool
    {
        $oldDate = $booking->scheduled_at;

        if ($newTime instanceof TandemDate) {
            DB::transaction(function () use ($booking, $newTime): void {
                $slot = TandemDate::lockForUpdate()->findOrFail($newTime->id);

                if (! $slot->hasPlaceFor($booking)) {
                    throw new BookingUnavailableException(
                        "That date is full ({$slot->capacity} of {$slot->capacity} places taken) and this booking needs 1. Pick another date, or raise the date's capacity first."
                    );
                }

                $booking->tandem_date_id = $slot->id;
                $booking->scheduled_at = $slot->starts_at;
                $booking->status = BookingStatus::Rescheduled;
                $booking->save();
            });
        } else {
            $booking->tandem_date_id = null;
            $booking->scheduled_at = $newTime;
            $booking->status = BookingStatus::Rescheduled;
            $booking->save();
        }

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
