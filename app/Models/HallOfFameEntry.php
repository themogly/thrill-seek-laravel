<?php

namespace App\Models;

use App\Observers\ImageOptimizationObserver;
use App\Observers\SiteContentObserver;
use Database\Factories\HallOfFameEntryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([SiteContentObserver::class, ImageOptimizationObserver::class])]
class HallOfFameEntry extends Model
{
    /** @use HasFactory<HallOfFameEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'milestone',
        'achieved_on',
        'note',
        'image',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'achieved_on' => 'date',
        ];
    }

    /**
     * Public URL for the entry image. Seeded entries reference bundled site
     * images by absolute path; admin uploads are stored on the public disk.
     *
     * @return Attribute<string, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: fn (): string => str_starts_with($this->image, '/')
            ? $this->image
            : Storage::disk('public')->url($this->image));
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
}
