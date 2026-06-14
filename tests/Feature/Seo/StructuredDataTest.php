<?php

namespace Tests\Feature\Seo;

use App\Models\NewsArticle;
use App\Models\Testimonial;
use App\Settings\GeneralSettings;
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
        Testimonial::factory()->create(['rating' => 5]);
        Testimonial::factory()->create(['rating' => 4]);

        $this->get('/testimonials')
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"reviewCount":2', false);
    }

    public function test_news_article_has_article_and_breadcrumb_jsonld(): void
    {
        $article = NewsArticle::factory()->create(['slug' => 'jsonld-news']);

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }
}
