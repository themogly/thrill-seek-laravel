<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Support\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A mailable whose subject and body come from an admin-editable
 * EmailTemplate, with {{ placeholder }} variables substituted.
 */
class TemplatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $renderedSubject;

    public string $renderedBody;

    /**
     * @param  array<string, string>  $variables
     */
    public function __construct(EmailTemplate $template, array $variables)
    {
        $rendered = TemplateRenderer::render($template, $variables);
        $this->renderedSubject = $rendered['subject'];
        $this->renderedBody = $rendered['body'];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.templated',
            with: ['body' => $this->renderedBody],
        );
    }
}
