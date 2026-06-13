<?php

namespace Tests\Feature\News;

use App\Models\CourseDate;
use App\Models\Location;
use App\Models\NewsArticle;
use App\Models\Product;
use Tests\TestCase;

class NewsPublicTest extends TestCase
{
    public function test_index_lists_published_articles_newest_first(): void
    {
        NewsArticle::factory()->create(['title' => 'Older Story', 'published_at' => now()->subWeek()]);
        NewsArticle::factory()->create(['title' => 'Newer Story', 'published_at' => now()->subDay()]);

        $this->get('/news')
            ->assertOk()
            ->assertSeeInOrder(['Newer Story', 'Older Story']);
    }

    public function test_index_hides_drafts_and_future_dated_posts(): void
    {
        NewsArticle::factory()->create(['title' => 'Live Story']);
        NewsArticle::factory()->draft()->create(['title' => 'Draft Story']);
        NewsArticle::factory()->scheduled()->create(['title' => 'Future Story']);

        $this->get('/news')
            ->assertOk()
            ->assertSee('Live Story')
            ->assertDontSee('Draft Story')
            ->assertDontSee('Future Story');
    }

    public function test_single_article_renders(): void
    {
        $article = NewsArticle::factory()->create([
            'title' => 'Season Opener',
            'slug' => 'season-opener',
            'lead' => 'Here we go.',
            'body' => '<p>The season is open.</p>',
        ]);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertSee('Season Opener')
            ->assertSee('Here we go.')
            ->assertSee('The season is open.', false);
    }

    public function test_unpublished_and_missing_articles_404(): void
    {
        $draft = NewsArticle::factory()->draft()->create(['slug' => 'secret-draft']);

        $this->get("/news/{$draft->slug}")->assertNotFound();
        $this->get('/news/does-not-exist')->assertNotFound();
    }

    public function test_linked_course_panel_shows_live_availability_and_book_cta(): void
    {
        $product = Product::factory()->aff()->create();
        $location = Location::factory()->create(['name' => 'Seville, Spain']);
        $course = CourseDate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(4)->toDateString(),
            'capacity' => 8,
        ]);
        $article = NewsArticle::factory()->create(['slug' => 'seville-course', 'course_date_id' => $course->id]);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertSee('Seville, Spain')
            ->assertSee('places left')
            ->assertSee('/book/aff?course='.$course->id, false);
    }

    public function test_news_routes_404_and_nav_hides_when_disabled(): void
    {
        $this->setFeature('news_enabled', false);

        NewsArticle::factory()->create(['slug' => 'hidden']);

        $this->get('/news')->assertNotFound();
        $this->get('/news/hidden')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('href="/news"', false);
    }
}
