<?php

namespace App\Actions;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendNewsletterCampaign
{
    /**
     * Record the campaign and queue one send per confirmed subscriber. Only
     * the `confirmed` scope is targeted, so pending (un-opted-in) and
     * unsubscribed/suppressed addresses are never sent to. Each mail queues
     * independently, so one bad address cannot fail the batch.
     */
    public function handle(string $subject, string $body, ?User $sender): NewsletterCampaign
    {
        $recipients = NewsletterSubscriber::confirmed()->get();

        $campaign = NewsletterCampaign::create([
            'subject' => $subject,
            'body' => $body,
            'recipient_count' => $recipients->count(),
            'sent_at' => now(),
            'user_id' => $sender?->id,
        ]);

        $recipients->each(function (NewsletterSubscriber $subscriber) use ($campaign): void {
            Mail::to($subscriber->email)->queue(new NewsletterCampaignMail($campaign, $subscriber));
        });

        return $campaign;
    }
}
