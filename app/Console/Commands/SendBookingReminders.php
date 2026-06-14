<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Queue jump reminders and outstanding-balance reminders for upcoming bookings';

    private const JUMP_REMINDER_DAYS = 7;

    private const BALANCE_REMINDER_DAYS = 14;

    public function handle(): int
    {
        $this->sendJumpReminders();
        $this->sendBalanceReminders();

        return self::SUCCESS;
    }

    private function sendJumpReminders(): void
    {
        $bookings = Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Rescheduled])
            ->whereBetween('scheduled_at', [now(), now()->addDays(self::JUMP_REMINDER_DAYS)])
            ->whereNull('reminder_sent_at')
            ->get();

        $template = EmailTemplate::findByKey('jump_reminder');

        foreach ($bookings as $booking) {
            Mail::to($booking->email)->queue(new TemplatedMail($template, [
                'name' => $booking->name,
                'reference' => $booking->reference,
                'product' => $booking->product->name ?? 'your jump',
                'date' => (string) $booking->scheduledLabel(),
                'location' => $booking->locationName() ?? 'to be confirmed',
            ]));

            $booking->forceFill(['reminder_sent_at' => now()])->saveQuietly();
        }

        $this->info("Queued {$bookings->count()} jump reminder(s).");
    }

    private function sendBalanceReminders(): void
    {
        $bookings = Booking::query()
            ->withOutstandingBalance()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Rescheduled])
            ->whereBetween('scheduled_at', [now(), now()->addDays(self::BALANCE_REMINDER_DAYS)])
            ->whereNull('balance_reminder_sent_at')
            ->get();

        $template = EmailTemplate::findByKey('balance_reminder');

        foreach ($bookings as $booking) {
            Mail::to($booking->email)->queue(new TemplatedMail($template, [
                'name' => $booking->name,
                'reference' => $booking->reference,
                'product' => $booking->product->name ?? 'your booking',
                'balance' => $booking->formatted_balance_due,
                'date' => (string) $booking->scheduled_at?->format('l j F Y'),
            ]));

            $booking->forceFill(['balance_reminder_sent_at' => now()])->saveQuietly();
        }

        $this->info("Queued {$bookings->count()} balance reminder(s).");
    }
}
