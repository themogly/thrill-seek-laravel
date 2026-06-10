<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\AvailabilitySlotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An admin-defined jump date/time with limited capacity.
 *
 * @property Carbon $starts_at
 */
class AvailabilitySlot extends Model
{
    /** @use HasFactory<AvailabilitySlotFactory> */
    use HasFactory;

    protected $fillable = [
        'starts_at',
        'capacity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Bookings that occupy a place (anything not cancelled). */
    public function activeBookingsCount(): int
    {
        return $this->bookings()
            ->where('status', '!=', BookingStatus::Cancelled)
            ->count();
    }

    /**
     * @return Attribute<int, never>
     */
    protected function remainingCapacity(): Attribute
    {
        return Attribute::make(get: fn (): int => max(0, $this->capacity - $this->activeBookingsCount()));
    }

    public function isFull(): bool
    {
        return $this->remaining_capacity === 0;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }
}
