<?php

namespace Tests\Feature\News;

use App\Filament\Resources\News\Pages\CreateNews;
use App\Models\CourseDate;
use App\Models\Location;
use App\Models\NewsArticle;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class NewsAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_create_an_article_with_an_auto_slug(): void
    {
        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'Big News Today',
                'slug' => 'big-news-today',
                'body' => '<p>Something happened.</p>',
                'published' => true,
                'published_at' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('news_articles', ['slug' => 'big-news-today', 'title' => 'Big News Today']);
    }

    public function test_slug_must_be_unique(): void
    {
        NewsArticle::factory()->create(['slug' => 'taken']);

        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'Another',
                'slug' => 'taken',
                'body' => '<p>x</p>',
                'published_at' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_lead_is_optional(): void
    {
        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'No Lead Here',
                'slug' => 'no-lead-here',
                'body' => '<p>Body only.</p>',
                'lead' => null,
                'published_at' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_course_link_options_show_location_and_dates(): void
    {
        $product = Product::factory()->aff()->create();
        $location = Location::factory()->create(['name' => 'Seville, Spain']);
        CourseDate::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(4)->toDateString(),
        ]);

        Livewire::test(CreateNews::class)->assertSee('Seville, Spain · ');
    }
}
