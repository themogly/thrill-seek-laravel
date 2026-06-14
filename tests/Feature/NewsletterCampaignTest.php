<?php

namespace Tests\Feature;

use App\Actions\SendNewsletterCampaign;
use App\Enums\NewsletterCampaignStatus;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterCampaignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_sending_only_targets_confirmed_subscribers(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'in@example.com']);
        NewsletterSubscriber::factory()->pending()->create(['email' => 'maybe@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

        $campaign = NewsletterCampaign::factory()->create();

        app(SendNewsletterCampaign::class)->handle($campaign);

        $campaign->refresh();
        $this->assertSame(NewsletterCampaignStatus::Sent, $campaign->status);
        $this->assertSame(1, $campaign->recipient_count);
        $this->assertNotNull($campaign->rendered_html, 'Rendered HTML is frozen at send.');

        Mail::assertQueued(NewsletterCampaignMail::class, 1);
        Mail::assertQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $m): bool => $m->hasTo('in@example.com'));
        Mail::assertNotQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $m): bool => $m->hasTo('gone@example.com'));
        Mail::assertNotQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $m): bool => $m->hasTo('maybe@example.com'));
    }

    public function test_sending_is_idempotent_under_retry_or_overlap(): void
    {
        NewsletterSubscriber::factory()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();

        $action = app(SendNewsletterCampaign::class);
        $action->handle($campaign);
        // A retry / overlapping tick must not double-send.
        $action->handle($campaign->refresh());

        Mail::assertQueued(NewsletterCampaignMail::class, 2);
        $this->assertSame(2, $campaign->refresh()->recipient_count);
        $this->assertSame(2, $campaign->recipients()->count());
    }
}
