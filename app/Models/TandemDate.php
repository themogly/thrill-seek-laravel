<?php

namespace App\Models;

use App\Contracts\GuardsDeletion;
use App\Enums\BookingStatus;
use App\Models\Concerns\RefusesGuardedDeletion;
use Database\Factories\TandemDateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An admin-defined jump date/time with limited capacity.
 *
 * @property Carbon $starts_at
 */
class TandemDate extends Model implements GuardsDeletion
{
    /** @use HasFactory<TandemDateFactory> */
    use HasFactory, RefusesGuardedDeletion;

    protected $fillable = [
        'location_id',
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

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
     * Whether a booking can move onto this date: it already holds a place here,
     * or one is free. The same count the online checkout uses (isFull: every
     * booking that isn't cancelled, unpaid checkout holds included). A booking
     * is one jumper, so it needs one place.
     */
    public function hasPlaceFor(Booking $booking): bool
    {
        $alreadyHere = $booking->tandem_date_id === $this->id && $booking->status !== BookingStatus::Cancelled;

        return $alreadyHere || ! $this->isFull();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function deletionBlocker(): ?string
    {
        $booked = $this->activeBookingsCount();

        if ($booked > 0) {
            return "{$booked} customer(s) are booked on this date. Reschedule them first.";
        }

        // Cancelled bookings still record which date they were for, and the
        // database refuses to unlink them (prompt 008), so the button says so too.
        $cancelled = $this->bookings()->count();

        return $cancelled === 0
            ? null
            : "{$cancelled} cancelled booking(s) still record this date, so it stays as history.";
    }
}
