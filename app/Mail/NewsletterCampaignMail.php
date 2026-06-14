<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Settings\GeneralSettings;
use App\Support\NewsletterRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * One block-based newsletter to one confirmed subscriber. Renders the campaign
 * blocks to email-safe HTML (or reuses the HTML frozen at send), wraps them in
 * the gforce shell, and carries a signed one-click unsubscribe footer.
 */
class NewsletterCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterCampaign $campaign,
        public NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject,
            replyTo: [new Address(app(GeneralSettings::class)->email, 'G-Force Skydiving')],
        );
    }

    public function content(): Content
    {
        $renderer = app(NewsletterRenderer::class);

        // Frozen HTML for a sent campaign; legacy plain body; else render live.
        $body = $this->campaign->rendered_html
            ?? ($this->campaign->blocks !== null
                ? $renderer->renderBody($this->campaign)
                : nl2br(e((string) $this->campaign->body)));

        $unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber->getKey()]);

        return new Content(
            markdown: 'mail.newsletter',
            text: 'mail.newsletter-text',
            with: [
                'body' => $body,
                'preheader' => $this->campaign->preheader,
                'textBody' => $renderer->plainText($body),
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }
}
