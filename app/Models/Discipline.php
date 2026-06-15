<?php

namespace App\Models;

use App\Observers\SiteContentObserver;
use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A skydiving discipline an instructor teaches (Tandem / AFF / Coaching).
 * A small, CMS-managed lookup; busts the public instructor cache when edited.
 */
#[ObservedBy([SiteContentObserver::class])]
class Discipline extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<Instructor, $this> */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Display order used everywhere disciplines are listed.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
