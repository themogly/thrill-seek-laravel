<?php

namespace App\Jobs;

use App\Mail\CourseMessageMail;
use App\Models\CourseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one course message to one recipient. One job per recipient so a
 * single bounce/failure retries alone instead of killing the batch. This job,
 * not CourseMessageMail, is the retry unit — so it carries the same delivery
 * rules as App\Mail\QueuedMailable.
 */
class SendCourseMessageToRecipient implements ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [30, 120, 600];

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

    public function failed(Throwable $e): void
    {
        Log::error('Email failed after all retries', [
            'mailable' => CourseMessageMail::class,
            'exception' => $e->getMessage(),
        ]);
    }
}
