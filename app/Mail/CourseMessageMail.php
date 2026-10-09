<?php

namespace App\Mail;

use App\Mail\Concerns\EmbedsMailLogo;
use App\Models\CourseMessage;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One course broadcast to one recipient. Not ShouldQueue itself — the
 * per-recipient SendCourseMessageToRecipient job queues the send so one
 * bad address cannot fail a whole batch.
 */
class CourseMessageMail extends Mailable
{
    use EmbedsMailLogo, Queueable, SerializesModels;

    public function __construct(
        public CourseMessage $courseMessage,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->courseMessage->subject,
            replyTo: [new Address(app(GeneralSettings::class)->email, 'G-Force Skydiving')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.course-message',
            with: [
                'body' => $this->courseMessage->body,
                'recipientName' => $this->recipientName,
                'course' => $this->courseMessage->courseDate,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return $this->courseMessage->documents
            ->map(fn ($document): Attachment => Attachment::fromStorageDisk('local', $document->file_path)
                ->as($document->original_filename)
                ->withMime($document->mime_type))
            ->all();
    }
}
