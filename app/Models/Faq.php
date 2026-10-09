<?php

namespace App\Models;

use App\Enums\FaqPage;
use App\Observers\SiteContentObserver;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A per-page FAQ shown as an accordion on its service page and emitted as that
 * page's FAQPage structured data. Cache-busted via SiteContentObserver on save.
 *
 * @property FaqPage $page
 */
#[ObservedBy(SiteContentObserver::class)]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'page',
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'page' => FaqPage::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Plain-text answer for the FAQPage structured data (parity with the visible text). */
    public function plainAnswer(): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags(str_replace(['</p>', '</li>', '<br>', '<br/>', '<br />'], ' ', (string) $this->answer))));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Not `scopeForPage`: that name hijacked the builder's own forPage($page, $perPage)
     * paginator and 500'd the admin FAQ list (NoModelScopeShadowsBuilderTest guards it).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOnPage(Builder $query, FaqPage $page): Builder
    {
        return $query->where('page', $page->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** A stable DOM id for the accordion's collapsible panel. */
    public function panelId(): string
    {
        return 'faq-'.$this->page->value.'-'.$this->id;
    }
}
