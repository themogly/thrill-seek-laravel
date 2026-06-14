<?php

namespace Tests\Feature\Newsletter;

use App\Actions\SendNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\CreateNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\EditNewsletterCampaign;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsArticle;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Models\User;
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

        // Sent only to the test address; no recipient/history rows; still a draft.
        Mail::assertQueued(NewsletterCampaignMail::class, 1);
        Mail::assertQueued(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $m): bool => $m->hasTo('admin@example.com'));
        $this->assertSame(0, NewsletterCampaignRecipient::count());
        $this->assertFalse($campaign->refresh()->isSent());
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

    public function test_logo_block_renders_with_an_absolute_url_and_alt(): void
    {
        $html = $this->renderBody([['type' => 'logo', 'data' => []]]);

        $this->assertStringContainsString('src="'.url('/images/logo.png').'"', $html);
        $this->assertStringContainsString('alt="G-Force Skydiving"', $html);
        // Explicit dimensions for email clients; no flexbox.
        $this->assertStringContainsString('width="180"', $html);
        $this->assertStringContainsString('height="64"', $html);
        $this->assertStringNotContainsString('display:flex', $html);
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
