<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SiteContentObserver;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(SiteContentObserver::class)]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
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
        ];
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
