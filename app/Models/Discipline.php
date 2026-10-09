<?php

namespace App\Models;

use App\Contracts\GuardsDeletion;
use App\Models\Concerns\RefusesGuardedDeletion;
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
class Discipline extends Model implements GuardsDeletion
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory, RefusesGuardedDeletion;

    /** The disciplines the Tandem / AFF / Coaching pages list instructors by. */
    public const TANDEM = 'tandem';

    public const AFF = 'aff';

    public const COACHING = 'coaching';

    public const PAGE_SLUGS = [self::TANDEM, self::AFF, self::COACHING];

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

    /** One of the three disciplines a public page depends on — its slug is fixed. */
    public function isPageDiscipline(): bool
    {
        return in_array($this->getOriginal('slug') ?? $this->slug, self::PAGE_SLUGS, true);
    }

    public function deletionBlocker(): ?string
    {
        return $this->isPageDiscipline()
            ? "The {$this->name} page lists its instructors from this discipline, so it can't be deleted."
            : null;
    }
}
