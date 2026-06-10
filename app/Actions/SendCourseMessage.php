<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SendCourseMessageToRecipient;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SendCourseMessage
{
    /**
     * Record the message (audit trail) and queue one send job per student
     * on the course. Cancelled bookings and unpaid checkout holds are
     * excluded by CourseDate::messageableBookings().
     *
     * @param  Collection<int, Document>  $documents
     */
    public function handle(
        CourseDate $courseDate,
        string $subject,
        string $body,
        Collection $documents,
        ?User $sender,
        string $source = 'manual',
    ): CourseMessage {
        $totalBytes = (int) $documents->sum('size_bytes');

        if ($totalBytes > Document::MAX_MESSAGE_ATTACHMENT_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Attachments total %.1f MB — the limit per message is %d MB.',
                $totalBytes / (1024 * 1024),
                Document::MAX_MESSAGE_ATTACHMENT_BYTES / (1024 * 1024),
            ));
        }

        $recipients = $courseDate->messageableBookings();

        $message = CourseMessage::create([
            'course_date_id' => $courseDate->id,
            'user_id' => $sender?->id,
            'subject' => $subject,
            'body' => $body,
            'source' => $source,
            'recipients' => $recipients
                ->map(fn (Booking $booking): array => [
                    'booking_id' => $booking->id,
                    'name' => $booking->name,
                    'email' => $booking->email,
                ])
                ->values()
                ->all(),
        ]);

        $message->documents()->sync($documents->pluck('id'));

        foreach ($recipients as $booking) {
            SendCourseMessageToRecipient::dispatch($message, $booking->email, $booking->name);
        }

        return $message;
    }
}
