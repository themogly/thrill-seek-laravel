<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Database\Factories\HallOfFameEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HallOfFameEntry extends Model
{
    /** @use HasFactory<HallOfFameEntryFactory> */
    use FlushesContentCache, HasFactory;

    protected $fillable = [
        'name',
        'milestone',
        'image',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
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

    protected static function contentCacheKeys(): array
    {
        return ['hall_of_fame'];
    }
}
