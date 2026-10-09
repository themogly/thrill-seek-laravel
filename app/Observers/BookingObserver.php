<?php

namespace App\Observers;

use App\Actions\SendBookingConfirmation;
use App\Enums\BookingStatus;
use App\Models\Booking;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        Booking::forgetPresenceCache();
    }

    public function deleted(Booking $booking): void
    {
        Booking::forgetPresenceCache();
    }

    /**
     * Email the customer whenever a booking becomes confirmed (including
     * re-confirmation after a reschedule, which confirms the new date). A booking
     * CREATED already confirmed by the admin is CreateBooking's call (its toggle).
     */
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status') || $booking->status !== BookingStatus::Confirmed) {
            return;
        }

        app(SendBookingConfirmation::class)->handle($booking);
    }
}
