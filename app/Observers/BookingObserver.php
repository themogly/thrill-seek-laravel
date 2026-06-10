<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingObserver
{
    /**
     * Email the customer whenever a booking becomes confirmed (including
     * re-confirmation after a reschedule, which confirms the new date).
     */
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status') || $booking->status !== BookingStatus::Confirmed) {
            return;
        }

        try {
            Mail::to($booking->email)->queue(new TemplatedMail(
                EmailTemplate::findByKey('booking_confirmed'),
                [
                    'name' => $booking->name,
                    'reference' => $booking->reference,
                    'product' => $booking->product->name ?? 'your jump',
                    'date' => $booking->scheduled_at?->format('l j F Y, H:i') ?? 'to be confirmed',
                    'location' => $booking->locationName() ?? 'to be confirmed',
                ],
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to queue booking confirmation email', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
