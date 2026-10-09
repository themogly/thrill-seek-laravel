<?php

namespace Tests\Feature\Newsletter;

use App\Actions\DuplicateNewsletterCampaign;
use App\Actions\SendNewsletterCampaign;
use App\Enums\NewsletterCampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\Pages\CreateNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\EditNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsArticle;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Support\MailLogo;
use App\Support\NewsletterRenderer;
use App\Support\NewsletterStarterTemplates;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    private function renderBody(array $blocks): string
    {
        $campaign = NewsletterCampaign::factory()->create(['blocks' => $blocks, 'rendered_html' => null]);

        return app(NewsletterRenderer::class)->renderBody($campaign);
    }

    public function test_each_block_type_renders_email_safe_html(): void
    {
        $html = $this->renderBody([
            ['type' => 'heading', 'data' => ['text' => 'Hello Jumpers', 'level' => 'h1']],
            ['type' => 'paragraph', 'data' => ['text' => '<p>Come fly.</p>']],
            ['type' => 'image', 'data' => ['image' => '/images/hero-skydive.jpg', 'caption' => 'Freefall', 'link' => '/tandem']],
            ['type' => 'button', 'data' => ['label' => 'Book now', 'url' => '/tandem']],
            ['type' => 'divider', 'data' => []],
            ['type' => 'two_column', 'data' => ['image' => '/images/aff.jpg', 'heading' => 'AFF', 'text' => 'Go pro.', 'image_side' => 'left']],
        ]);

        $this->assertStringContainsString('Hello Jumpers', $html);
        $this->assertStringContainsString('Come fly.', $html);
        // Email-safe: inline-styled button, table layout, absolute image URL, no flexbox.
        $this->assertStringContainsString('background-color:#2f8de4', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString(url('/images/hero-skydive.jpg'), $html);
        $this->assertStringNotContainsString('display:flex', $html);
        // Button/link paths are absolute.
        $this->assertStringContainsString(url('/tandem'), $html);
    }

    public function test_dynamic_block_resolves_to_static_content(): void
    {
        NewsArticle::factory()->create(['title' => 'Fresh Dropzone News', 'slug' => 'fresh-news']);

        $html = $this->renderBody([['type' => 'latest_news', 'data' => []]]);

        $this->assertStringContainsString('Fresh Dropzone News', $html);
        $this->assertStringContainsString(url('/news/fresh-news'), $html);
    }

    public function test_rendered_html_is_frozen_at_send(): void
    {
        NewsletterSubscriber::factory()->create();
        $article = NewsArticle::factory()->create(['title' => 'Original Headline']);
        $campaign = NewsletterCampaign::factory()->create([
            'blocks' => [['type' => 'latest_news', 'data' => []]],
            'rendered_html' => null,
        ]);

        app(SendNewsletterCampaign::class)->handle($campaign);
        $campaign->refresh();

        $this->assertStringContainsString('Original Headline', (string) $campaign->rendered_html);

        // Changing the article afterwards does not alter the frozen send.
        $article->update(['title' => 'Changed Headline']);
        $this->assertStringContainsString('Original Headline', (string) $campaign->refresh()->rendered_html);
        $this->assertStringNotContainsString('Changed Headline', (string) $campaign->rendered_html);
    }

    public function test_every_send_includes_the_unsubscribe_footer(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();
        $campaign = NewsletterCampaign::factory()->create();

        $html = (new NewsletterCampaignMail($campaign, $subscriber))->render();

        $this->assertStringContainsString('/newsletter/unsubscribe/'.$subscriber->id, $html);
    }

    public function test_send_test_does_not_touch_real_subscribers_or_history(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        NewsletterSubscriber::factory()->count(3)->create(); // real confirmed subscribers
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->id])
            ->callAction('sendTest', data: ['email' => 'admin@example.com']);

        // Sent (synchronously) only to the test address; no recipient/history rows; still a draft.
        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $m): bool => $m->hasTo('admin@example.com'));
        $this->assertSame(0, NewsletterCampaignRecipient::count());
        $this->assertFalse($campaign->refresh()->isSent());
    }

    public function test_send_test_actually_delivers_through_the_mailer(): void
    {
        // No Mail::fake — the real (array) transport, through whatever path the
        // action takes. A queued test-send carries an unsaved subscriber that the
        // worker can never restore, so it must go synchronously.
        $this->actingAs(User::factory()->create());
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->id])
            ->callAction('sendTest', data: ['email' => 'admin@example.com'])
            ->assertNotified('Test sent');

        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $this->assertSame('admin@example.com', $sent->first()->getEnvelope()->getRecipients()[0]->getAddress());
    }

    public function test_a_failed_test_send_says_so_instead_of_claiming_success(): void
    {
        $this->actingAs(User::factory()->create());
        $campaign = NewsletterCampaign::factory()->create();
        // An SMTP server that isn't there: the transport throws.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 9]);

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->id])
            ->callAction('sendTest', data: ['email' => 'admin@example.com'])
            ->assertNotified('Test email failed');
    }

    public function test_builder_create_page_offers_the_block_types(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateNewsletterCampaign::class)
            ->assertOk()
            ->assertSee('Add a block')
            ->assertSee('Start from a template');
    }

    public function test_starter_template_seeds_the_expected_blocks_on_create(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(CreateNewsletterCampaign::class)
            ->set('data.starter_template', 'new_dates');

        $types = collect($component->get('data.blocks'))->pluck('type')->all();
        $this->assertSame(['logo', 'heading', 'paragraph', 'featured_course', 'button'], $types);

        // Switching to Blank clears the content.
        $component->set('data.starter_template', 'blank');
        $this->assertSame([], $component->get('data.blocks'));
    }

    public function test_every_starter_template_uses_only_known_block_types(): void
    {
        foreach (NewsletterStarterTemplates::all() as $key => $template) {
            foreach ($template['blocks'] as $block) {
                $this->assertContains(
                    $block['type'],
                    NewsletterRenderer::BLOCK_TYPES,
                    "Template {$key} uses unknown block: {$block['type']}",
                );
            }
        }
    }

    public function test_every_starter_template_renders_email_safe(): void
    {
        // A template error must never ship silently — render each through the real
        // mailable and assert no leaked/escaped markup or Livewire markers.
        NewsArticle::factory()->create(['title' => 'Template Render News', 'slug' => 'tpl-news']);
        $subscriber = NewsletterSubscriber::factory()->create();

        foreach (NewsletterStarterTemplates::all() as $key => $template) {
            $campaign = NewsletterCampaign::factory()->create([
                'rendered_html' => null,
                'blocks' => $template['blocks'],
            ]);

            $html = (new NewsletterCampaignMail($campaign, $subscriber))->render();

            foreach (['[if BLOCK]', '[if ENDBLOCK]', '&lt;table', '&lt;h3', '<pre', '<code>'] as $leak) {
                $this->assertStringNotContainsString($leak, $html, "Template {$key} leaked: {$leak}");
            }
            // Branded footer present on every send.
            $this->assertStringContainsString('Unsubscribe instantly', $html);
            $this->assertStringContainsString('/newsletter/unsubscribe/'.$subscriber->id, $html);
        }
    }

    public function test_logo_block_renders_the_embedded_logo_with_alt(): void
    {
        $html = $this->renderBody([['type' => 'logo', 'data' => []]]);

        // The logo PNG travels inside the email as an inline CID part (011 — replaced the
        // APP_URL hot-link, which 401s behind staging basic-auth and is blocked by many
        // clients). Never a relative path, never an inline SVG (Outlook/Gmail won't render it).
        $this->assertStringContainsString('src="cid:'.MailLogo::CID.'"', $html);
        $this->assertStringNotContainsString('/images/email/logo.png', $html);
        $this->assertStringContainsString('alt="G-Force Skydiving"', $html);
        $this->assertStringNotContainsString('<svg', $html);
        // Explicit dimensions for email clients; no flexbox.
        $this->assertStringContainsString('width="180"', $html);
        $this->assertStringContainsString('height="68"', $html);
        $this->assertStringNotContainsString('display:flex', $html);
    }

    public function test_duplicating_copies_blocks_and_meta_as_an_independent_draft(): void
    {
        $original = NewsletterCampaign::factory()->create([
            'name' => 'June update',
            'subject' => 'June news',
            'preheader' => 'Read on',
            'blocks' => [['type' => 'heading', 'data' => ['text' => 'Original', 'level' => 'h1']]],
        ]);

        $copy = app(DuplicateNewsletterCampaign::class)->handle($original);

        // All content + meta copied, with a sensible default name.
        $this->assertSame('Copy of June update', $copy->name);
        $this->assertSame('June news', $copy->subject);
        $this->assertSame('Read on', $copy->preheader);
        $this->assertSame($original->blocks, $copy->blocks);
        $this->assertTrue($copy->status === NewsletterCampaignStatus::Draft);

        // Editing the copy never touches the original.
        $copy->update(['blocks' => [['type' => 'heading', 'data' => ['text' => 'Changed', 'level' => 'h1']]]]);
        $this->assertSame('Original', $original->refresh()->blocks[0]['data']['text']);
    }

    public function test_duplicating_a_sent_newsletter_resets_all_send_state(): void
    {
        $sender = User::factory()->create();
        $sent = NewsletterCampaign::factory()->create([
            'name' => 'Last month',
            'status' => NewsletterCampaignStatus::Sent,
            'rendered_html' => '<p>frozen</p>',
            'recipient_count' => 42,
            'sent_at' => now()->subWeek(),
            'scheduled_at' => now()->subWeek(),
        ]);
        // Real send history on the original.
        NewsletterCampaignRecipient::create([
            'newsletter_campaign_id' => $sent->id,
            'newsletter_subscriber_id' => NewsletterSubscriber::factory()->create()->id,
            'sent_at' => now()->subWeek(),
        ]);

        $copy = app(DuplicateNewsletterCampaign::class)->handle($sent, $sender->id);

        // A fresh, editable draft — never something that looks sent or could resend.
        $this->assertTrue($copy->status === NewsletterCampaignStatus::Draft);
        $this->assertFalse($copy->isSent());
        $this->assertNull($copy->rendered_html);
        $this->assertSame(0, $copy->recipient_count);
        $this->assertNull($copy->sent_at);
        $this->assertNull($copy->scheduled_at);
        // Send history is NOT copied.
        $this->assertSame(0, $copy->recipients()->count());
        $this->assertSame($sender->id, $copy->user_id);
    }

    public function test_duplicate_action_creates_a_copy_from_the_list(): void
    {
        $this->actingAs(User::factory()->create());
        $campaign = NewsletterCampaign::factory()->create(['name' => 'Spring']);

        Livewire::test(ListNewsletterCampaigns::class)
            ->callTableAction('duplicate', $campaign);

        $this->assertDatabaseHas('newsletter_campaigns', [
            'name' => 'Copy of Spring',
            'status' => NewsletterCampaignStatus::Draft->value,
        ]);
    }

    public function test_livewire_morph_markers_are_stripped(): void
    {
        // Livewire injects these conditional comments around @if/@foreach in any
        // Blade view — they must never survive into newsletter HTML.
        $dirty = '<!--[if BLOCK]><![endif]--><table>x</table><!--[if ENDBLOCK]><![endif-->';
        $clean = NewsletterRenderer::stripLivewireMarkers(
            '<!--[if BLOCK]><![endif]--><table>x</table><!--[if ENDBLOCK]><![endif]-->'
        );

        $this->assertSame('<table>x</table>', $clean);
        $this->assertStringNotContainsString('[if BLOCK]', $clean);
        $this->assertStringNotContainsString('[if ENDBLOCK]', $clean);
    }

    public function test_no_block_type_leaks_raw_or_escaped_markup_into_the_email(): void
    {
        // Every block, including the dynamic ones, in one campaign.
        NewsArticle::factory()->create(['title' => 'Dropzone Update', 'slug' => 'dz-update']);

        $subscriber = NewsletterSubscriber::factory()->create();
        $campaign = NewsletterCampaign::factory()->create([
            'rendered_html' => null,
            'blocks' => [
                ['type' => 'logo', 'data' => []],
                ['type' => 'heading', 'data' => ['text' => 'All blocks', 'level' => 'h1']],
                ['type' => 'paragraph', 'data' => ['text' => '<p>Intro.</p>']],
                ['type' => 'image', 'data' => ['image' => '/images/hero-skydive.jpg', 'caption' => 'Sky', 'link' => '/tandem']],
                ['type' => 'button', 'data' => ['label' => 'Book', 'url' => '/tandem']],
                ['type' => 'divider', 'data' => []],
                ['type' => 'two_column', 'data' => ['image' => '/images/aff.jpg', 'heading' => 'AFF', 'text' => 'Go pro.', 'image_side' => 'left', 'button_label' => 'See AFF', 'button_url' => '/aff']],
                ['type' => 'latest_news', 'data' => []],
                ['type' => 'featured_course', 'data' => []],
            ],
        ]);

        $html = (new NewsletterCampaignMail($campaign, $subscriber))->render();

        // No Livewire markers, and no markup shown as escaped/visible text.
        foreach (['[if BLOCK]', '[if ENDBLOCK]', '&lt;table', '&lt;h3', '&lt;p', '<pre', '<code>'] as $leak) {
            $this->assertStringNotContainsString($leak, $html, "Email leaked: {$leak}");
        }

        // The block HTML itself is present and rendered (not stripped away).
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('Dropzone Update', $html);
        $this->assertStringContainsString('All blocks', $html);
    }
}
