<?php

namespace Tests\Feature\News;

use App\Filament\Resources\News\Pages\EditNews;
use App\Models\NewsArticle;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A published article with no publish date never appears on the site — the
 * owner thinks it's live and it isn't. The date is required once Published is on.
 */
class PublishDateRequiredTest extends TestCase
{
    public function test_a_published_article_needs_a_publish_date(): void
    {
        $this->actingAs(User::factory()->create());
        $article = NewsArticle::factory()->create(['published' => true, 'published_at' => now()->subDay()]);

        Livewire::test(EditNews::class, ['record' => $article->getRouteKey()])
            ->fillForm(['published' => true, 'published_at' => null])
            ->call('save')
            ->assertHasFormErrors(['published_at' => 'required']);
    }

    public function test_a_draft_may_leave_the_date_empty(): void
    {
        $this->actingAs(User::factory()->create());
        $article = NewsArticle::factory()->create(['published' => false]);

        Livewire::test(EditNews::class, ['record' => $article->getRouteKey()])
            ->fillForm(['published' => false, 'published_at' => null])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
