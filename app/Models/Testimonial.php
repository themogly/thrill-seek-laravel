<?php

namespace App\Models;

use App\Observers\ImageOptimizationObserver;
use App\Observers\SiteContentObserver;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([SiteContentObserver::class, ImageOptimizationObserver::class])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'rating',
        'avatar',
        'photo',
        'quote',
        'excerpt',
        'featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'sort_order' => 'integer',
            'rating' => 'integer',
        ];
    }

    /**
     * Public URL for the uploaded avatar, or null when none is set (the display
     * then falls back to the initial-letter badge).
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (! $this->avatar) {
                return null;
            }

            return str_starts_with($this->avatar, '/')
                ? $this->avatar
                : Storage::disk('public')->url($this->avatar);
        });
    }

    /**
     * Public URL for the large action photo, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (! $this->photo) {
                return null;
            }

            return str_starts_with($this->photo, '/')
                ? $this->photo
                : Storage::disk('public')->url($this->photo);
        });
    }

    /**
     * The shorter quote used on the home page, falling back to the full quote.
     *
     * @return Attribute<string, never>
     */
    protected function homeQuote(): Attribute
    {
        return Attribute::make(get: fn (): string => filled($this->excerpt) ? $this->excerpt : $this->quote);
    }

    /**
     * Display order used by the public site.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Testimonials highlighted on the home page.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }
}
