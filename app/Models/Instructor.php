<?php

namespace App\Models;

use App\Observers\ImageOptimizationObserver;
use App\Observers\SiteContentObserver;
use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([SiteContentObserver::class, ImageOptimizationObserver::class])]
class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'bio',
        'photo',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Public URL for the uploaded photo, or null when none is set.
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
     * Disciplines this instructor teaches (Tandem / AFF / Coaching). Many-to-many
     * so a multi-discipline instructor is shown once with all their tags.
     *
     * @return BelongsToMany<Discipline, $this>
     */
    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Discipline::class)->ordered();
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
