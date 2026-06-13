<?php

namespace Tests\Feature\Seo;

use App\Models\NewsArticle;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_uses_absolute_urls(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('/tandem').'</loc>', false)
            // never a relative loc
            ->assertDontSee('<loc>/tandem</loc>', false);
    }

    public function test_sitemap_lists_published_articles_with_lastmod_and_hides_drafts(): void
    {
        $live = NewsArticle::factory()->create(['slug' => 'live-one']);
        NewsArticle::factory()->draft()->create(['slug' => 'draft-one']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('/news/live-one').'</loc>', false)
            ->assertSee('<lastmod>'.$live->updated_at->toAtomString().'</lastmod>', false)
            ->assertDontSee('/news/draft-one', false);
    }
}
