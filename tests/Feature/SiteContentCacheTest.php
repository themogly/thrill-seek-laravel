<?php

namespace Tests\Feature;

use App\Enums\FaqPage;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Testimonial;
use App\Support\SiteContent;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteContentCacheTest extends TestCase
{
    public function test_cached_payloads_contain_no_php_objects(): void
    {
        $this->seed(ProductSeeder::class);
        Testimonial::factory()->featured()->create();
        foreach (FaqPage::cases() as $page) {
            Faq::factory()->forPage($page)->create();
        }

        $content = app(SiteContent::class);
        $content->instructors();
        $content->featuredTestimonials();
        $content->allTestimonials();
        $content->galleryImages();
        $content->homeServices();
        $content->tandemProduct();
        $content->affProducts();
        $content->shopItems();
        $content->hallOfFame();
        $content->publishedNews();
        foreach (FaqPage::cases() as $page) {
            $content->faqs($page);
        }

        $keys = collect(SiteContent::KEYS_BY_MODEL)->flatten()->unique();

        foreach ($keys as $key) {
            $cached = Cache::get('site-content.'.$key);
            $this->assertNotNull($cached, "Expected site-content.{$key} to be warmed.");
            $this->assertObjectFree($cached, "site-content.{$key}");
        }
    }

    public function test_hydrated_models_keep_casts_relations_and_accessors(): void
    {
        $this->seed(ProductSeeder::class);

        $content = app(SiteContent::class);
        $content->tandemProduct(); // warm
        $product = $content->tandemProduct(); // read back from cache

        $this->assertNotNull($product);
        $this->assertSame('£260', $product->formatted_price);
        $this->assertIsArray($product->weight_charges);
        $this->assertCount(4, $product->addOns);
        $this->assertSame('£140', $product->addOns->firstWhere('name', 'Outside Camera')->formatted_price);
    }

    public function test_saving_a_content_model_busts_its_cache_keys(): void
    {
        $testimonial = Testimonial::factory()->featured()->create(['quote' => 'Original words.', 'excerpt' => null]);
        $this->get('/testimonials')->assertSee('Original words.');

        $testimonial->update(['quote' => 'Rewritten words.']);

        $this->get('/testimonials')->assertSee('Rewritten words.')->assertDontSee('Original words.');
    }

    public function test_product_changes_bust_the_pages_that_show_them(): void
    {
        $this->seed(ProductSeeder::class);
        $this->get('/tandem')->assertSee('£260');

        Product::where('slug', 'tandem-skydive')->firstOrFail()->update(['price_pence' => 28000]);

        $this->get('/tandem')->assertSee('£280');
    }

    private function assertObjectFree(mixed $value, string $path): void
    {
        if (is_object($value)) {
            $this->fail("Cached value at {$path} contains an object of type ".$value::class.'.');
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->assertObjectFree($item, "{$path}.{$key}");
            }
        }
    }
}
