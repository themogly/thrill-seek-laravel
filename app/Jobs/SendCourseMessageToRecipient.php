<?php

namespace App\Jobs;

use App\Mail\CourseMessageMail;
use App\Models\CourseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one course message to one recipient. One job per recipient so a
 * single bounce/failure retries alone instead of killing the batch.
 */
class SendCourseMessageToRecipient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public CourseMessage $courseMessage,
        public string $recipientEmail,
        public string $recipientName,
    ) {}

    public function handle(): void
    {
        Mail::to($this->recipientEmail)
            ->send(new CourseMessageMail($this->courseMessage, $this->recipientName));
    }
}
