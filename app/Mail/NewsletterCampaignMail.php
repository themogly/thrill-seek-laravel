<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Settings\GeneralSettings;
use App\Support\NewsletterRenderer;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/**
 * One block-based newsletter to one confirmed subscriber. Renders the campaign
 * blocks to email-safe HTML (or reuses the HTML frozen at send), wraps them in
 * the gforce shell, and carries a signed one-click unsubscribe: the footer link,
 * and the RFC 8058 List-Unsubscribe headers that give mail clients their own
 * "Unsubscribe" button (they POST to the same signed URL; prompt 026).
 */
class NewsletterCampaignMail extends QueuedMailable
{
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

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        $renderer = app(NewsletterRenderer::class);

        // Frozen HTML for a sent campaign; legacy plain body; else render live.
        $body = $this->campaign->rendered_html
            ?? ($this->campaign->blocks !== null
                ? $renderer->renderBody($this->campaign)
                : nl2br(e((string) $this->campaign->body)));

        $unsubscribeUrl = $this->unsubscribeUrl();

        // Render the body inside a plain-Blade (non-Markdown) shell so the
        // pre-built, inline-styled block HTML is emitted verbatim, then strip any
        // Livewire morph markers the Blade compiler injected around conditionals.
        $html = NewsletterRenderer::stripLivewireMarkers(
            view('mail.newsletter.shell', [
                'subject' => $this->campaign->subject,
                'preheader' => $this->campaign->preheader,
                'body' => $body,
                'unsubscribeUrl' => $unsubscribeUrl,
            ])->render()
        );

        return new Content(
            htmlString: $html,
            text: 'mail.newsletter-text',
            with: [
                'textBody' => $renderer->plainText($body),
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }

    /** One signed URL per subscriber: the footer link (GET) and the mail client's one-click POST. */
    private function unsubscribeUrl(): string
    {
        return URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber->getKey()]);
    }
}
