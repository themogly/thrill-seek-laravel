<?php

namespace App\Models;

use App\Contracts\GuardsDeletion;
use App\Observers\ImageOptimizationObserver;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A dropzone / operating location. Tandem dates and AFF courses both
 * happen at exactly one location.
 */
#[ObservedBy(ImageOptimizationObserver::class)]
class Location extends Model implements GuardsDeletion
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
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function deletionBlocker(): ?string
    {
        $dates = $this->tandemDates()->count() + $this->courseDates()->count();

        return $dates === 0
            ? null
            : "{$dates} tandem date(s) or course(s) use this location. Move or remove them first.";
    }
}
