<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SendCourseMessage;
use App\Enums\CourseDateStatus;
use App\Models\CourseReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendCourseReminders extends Command
{
    protected $signature = 'courses:send-reminders';

    protected $description = 'Send due per-course reminder emails to everyone on each course (idempotent via sent_at)';

    public function handle(SendCourseMessage $sendCourseMessage): int
    {
        $due = CourseReminder::unsent()
            ->with('courseDate')
            ->get()
            ->filter(fn (CourseReminder $reminder): bool => $reminder->isDue()
                && $reminder->courseDate->status !== CourseDateStatus::Cancelled);

        $sent = 0;

        foreach ($due as $reminder) {
            // Claim the reminder atomically before sending so two overlapping
            // scheduler ticks can never both send it.
            $claimed = DB::table('course_reminders')
                ->where('id', $reminder->id)
                ->whereNull('sent_at')
                ->update(['sent_at' => now()]);

            if ($claimed === 0) {
                continue;
            }

            $sendCourseMessage->handle(
                $reminder->courseDate,
                $reminder->subject,
                $reminder->body,
                collect(),
                null,
                source: 'reminder',
            );

            $sent++;
        }

        $this->info("Sent {$sent} course reminder(s).");

        return self::SUCCESS;
    }
}
