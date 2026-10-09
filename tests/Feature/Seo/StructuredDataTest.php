<?php

namespace Tests\Feature\Seo;

use App\Models\Customer;
use App\Models\NewsArticle;
use App\Models\Testimonial;
use App\Settings\GeneralSettings;
use App\Support\StructuredData;
use Database\Seeders\ProductSeeder;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    public function test_organization_jsonld_is_present_sitewide(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"SportsActivityLocation"', false)
            ->assertSee('"telephone":"'.app(GeneralSettings::class)->phone.'"', false);
    }

    public function test_tandem_has_product_offer_with_real_price(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/tandem')
            ->assertOk()
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"price":"260.00"', false)
            ->assertSee('"priceCurrency":"GBP"', false);
    }

    public function test_testimonials_has_aggregate_rating_from_real_ratings(): void
    {
        // Real ratings = reviews real customers submitted (007): at least three of them.
        Testimonial::factory()->create(['rating' => 5, 'customer_id' => Customer::factory()]);
        Testimonial::factory()->create(['rating' => 4, 'customer_id' => Customer::factory()]);
        Testimonial::factory()->create(['rating' => 5, 'customer_id' => Customer::factory()]);

        $this->get('/testimonials')
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"reviewCount":3', false)
            ->assertSee('"ratingValue":4.7', false);
    }

    public function test_news_article_has_article_and_breadcrumb_jsonld(): void
    {
        $article = NewsArticle::factory()->create(['slug' => 'jsonld-news']);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_breadcrumb_positions_are_sequential_and_ordered(): void
    {
        $data = StructuredData::breadcrumbs([
            'Home' => 'https://x/',
            'News' => 'https://x/news',
            'Article' => 'https://x/news/a',
        ]);

        $this->assertSame([1, 2, 3], array_column($data['itemListElement'], 'position'));
        $this->assertSame('Home', $data['itemListElement'][0]['name']);
        $this->assertSame('https://x/', $data['itemListElement'][0]['item']);
        $this->assertSame('Article', $data['itemListElement'][2]['name']);
    }
}
