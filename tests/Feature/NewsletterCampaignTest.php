<?php

namespace Tests\Feature;

use App\Actions\SendNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
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
        $confirmed = NewsletterSubscriber::factory()->create(['email' => 'in@example.com']);
        NewsletterSubscriber::factory()->pending()->create(['email' => 'maybe@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

        $campaign = app(SendNewsletterCampaign::class)->handle('Jump days!', 'Body copy', null);

        $this->assertSame(1, $campaign->recipient_count);
        $this->assertNotNull($campaign->sent_at);

        Mail::assertQueued(NewsletterCampaignMail::class, 1);
        Mail::assertQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail): bool => $mail->hasTo('in@example.com'));
        Mail::assertNotQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail): bool => $mail->hasTo('gone@example.com'));
        Mail::assertNotQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail): bool => $mail->hasTo('maybe@example.com'));
    }

    public function test_admin_can_compose_and_send_a_newsletter(): void
    {
        $this->actingAs(User::factory()->create());
        NewsletterSubscriber::factory()->count(2)->create();

        Livewire::test(ListNewsletterCampaigns::class)
            ->callAction('compose', data: [
                'subject' => 'Summer dates are live',
                'body' => '<p>Come jump with us.</p>',
            ]);

        $campaign = NewsletterCampaign::sole();
        $this->assertSame('Summer dates are live', $campaign->subject);
        $this->assertSame(2, $campaign->recipient_count);
        Mail::assertQueued(NewsletterCampaignMail::class, 2);
    }
}
