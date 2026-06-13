<?php

namespace App\Models;

use App\Observers\ImageOptimizationObserver;
use App\Observers\SiteContentObserver;
use Database\Factories\NewsArticleFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * An owner-written news article, optionally linked to an AFF course.
 *
 * @property Carbon|null $published_at
 */
#[ObservedBy([SiteContentObserver::class, ImageOptimizationObserver::class])]
class NewsArticle extends Model
{
    /** @use HasFactory<NewsArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'lead',
        'body',
        'featured_image',
        'published',
        'published_at',
        'byline',
        'seo_title',
        'seo_description',
        'course_date_id',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<CourseDate, $this> */
    public function courseDate(): BelongsTo
    {
        return $this->belongsTo(CourseDate::class);
    }

    /**
     * Live (never cached) — drafts and future-dated posts are excluded at read
     * time, so a scheduled post appears the moment its time passes.
     */
    public function isLive(): bool
    {
        return $this->published && $this->published_at !== null && $this->published_at->lte(Carbon::now());
    }

    /**
     * Published flag only; the published_at window is applied live on read so
     * the cached list can hold scheduled posts without a bust at publish time.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /**
     * Public URL for the featured image, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function featuredImageUrl(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (! $this->featured_image) {
                return null;
            }

            return str_starts_with($this->featured_image, '/')
                ? $this->featured_image
                : Storage::disk('public')->url($this->featured_image);
        });
    }
}
