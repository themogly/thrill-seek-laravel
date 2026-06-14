<?php

namespace Tests\Feature\Seo;

use App\Models\NewsArticle;
use Tests\TestCase;

class MetaTest extends TestCase
{
    public function test_pages_have_a_self_referencing_canonical_and_absolute_og(): void
    {
        $response = $this->get('/tandem');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.url('/tandem').'"', false);
        $response->assertSee('<meta property="og:url" content="'.url('/tandem').'"', false);
        // og:image is an absolute URL, never a relative path or build placeholder.
        $response->assertSee('<meta property="og:image" content="'.url('/').'/images', false);
        $response->assertDontSee('lovable', false);
        $response->assertSee('<html lang="en-GB">', false);
    }

    public function test_titles_are_unique_per_page(): void
    {
        $home = $this->get('/')->getContent();
        $tandem = $this->get('/tandem')->getContent();

        preg_match('/<title>(.*?)<\/title>/', $home, $h);
        preg_match('/<title>(.*?)<\/title>/', $tandem, $t);

        $this->assertNotEmpty($h[1]);
        $this->assertNotSame($h[1], $t[1]);
    }

    public function test_news_article_canonical_points_at_the_article(): void
    {
        $article = NewsArticle::factory()->create(['slug' => 'meta-test-article']);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/news/meta-test-article').'"', false);
    }
}
