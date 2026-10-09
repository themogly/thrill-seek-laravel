<?php

namespace App\Actions;

use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Settings\JumpPrepSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The one place the "your booking is confirmed" email is sent from: a booking
 * confirmed by an edit or the payment webhook (BookingObserver), and a booking the
 * admin creates already Confirmed with "Email the customer" on (CreateBooking).
 * Wrapped so a mail failure logs instead of breaking the request; returns whether
 * it was queued so a screen never claims a send that didn't happen.
 */
class SendBookingConfirmation
{
    public function handle(Booking $booking): bool
    {
        try {
            Mail::to($booking->email)->queue(new TemplatedMail(
                EmailTemplate::findByKey('booking_confirmed'),
                [
                    'name' => $booking->name,
                    'reference' => $booking->reference,
                    'product' => $booking->product->name ?? 'your jump',
                    'date' => $booking->scheduledLabel() ?? 'to be confirmed',
                    'location' => $booking->locationName() ?? 'to be confirmed',
                    // Single-source pre-jump info, tandem bookings only.
                    'jump_prep' => $booking->isTandem() ? app(JumpPrepSettings::class)->emailBlock() : '',
                ],
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to queue booking confirmation email', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
