<?php

namespace App\Models;

use App\Observers\ImageOptimizationObserver;
use App\Observers\SiteContentObserver;
use Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([SiteContentObserver::class, ImageOptimizationObserver::class])]
class GalleryImage extends Model
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory;

    protected $fillable = [
        'image',
        'alt_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Public URL for the image. Seeded rows reference bundled site images by
     * absolute path; admin uploads are stored on the public disk.
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
