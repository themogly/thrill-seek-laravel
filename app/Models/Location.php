<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ImageOptimizationObserver;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A dropzone / operating location. Tandem dates and AFF courses both
 * happen at exactly one location.
 */
#[ObservedBy(ImageOptimizationObserver::class)]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address_line',
        'town',
        'region',
        'postcode',
        'country',
        'lat',
        'lng',
        'description',
        'image',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'active' => 'boolean',
        ];
    }

    /** @return HasMany<TandemDate, $this> */
    public function tandemDates(): HasMany
    {
        return $this->hasMany(TandemDate::class);
    }

    /** @return HasMany<CourseDate, $this> */
    public function courseDates(): HasMany
    {
        return $this->hasMany(CourseDate::class);
    }

    /**
     * Public URL for the location image (bundled path or admin upload).
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if ($this->image === null) {
                return null;
            }

            return str_starts_with($this->image, '/')
                ? $this->image
                : Storage::disk('public')->url($this->image);
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
