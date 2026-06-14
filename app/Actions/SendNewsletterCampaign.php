<?php

namespace App\Actions;

use App\Enums\NewsletterCampaignStatus;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Support\NewsletterRenderer;
use Illuminate\Support\Facades\Mail;

class SendNewsletterCampaign
{
    public function __construct(private readonly NewsletterRenderer $renderer) {}

    /**
     * Freeze the rendered HTML, then queue one send per confirmed, non-
     * suppressed subscriber. Idempotent: a per-(campaign, subscriber) row is
     * claimed before queueing, so a job retry or an overlapping scheduler tick
     * can never double-send. Pending/unsubscribed addresses are never targeted.
     */
    public function handle(NewsletterCampaign $campaign): NewsletterCampaign
    {
        // Resolve dynamic blocks once and store the static HTML for the record.
        if ($campaign->rendered_html === null) {
            $campaign->update(['rendered_html' => $this->renderer->renderBody($campaign)]);
        }

        NewsletterSubscriber::confirmed()->each(function (NewsletterSubscriber $subscriber) use ($campaign): void {
            $claim = NewsletterCampaignRecipient::firstOrCreate([
                'newsletter_campaign_id' => $campaign->id,
                'newsletter_subscriber_id' => $subscriber->id,
            ]);

            // Already claimed (retry/overlap) — never send twice.
            if (! $claim->wasRecentlyCreated) {
                return;
            }

            Mail::to($subscriber->email)->queue(new NewsletterCampaignMail($campaign, $subscriber));
            $claim->update(['sent_at' => now()]);
        });

        $campaign->update([
            'status' => NewsletterCampaignStatus::Sent,
            'recipient_count' => $campaign->recipients()->count(),
            'sent_at' => $campaign->sent_at ?? now(),
        ]);

        return $campaign->refresh();
    }
}
