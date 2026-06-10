<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CourseDateStatus;
use App\Support\Money;
use Database\Factories\CourseDateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A scheduled AFF course: a start/end date at a location with limited
 * places. Price and deposit fall back to the linked product when no
 * override is set.
 *
 * @property CourseDateStatus $status
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 */
class CourseDate extends Model
{
    /** @use HasFactory<CourseDateFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'starts_on',
        'ends_on',
        'location',
        'price_pence',
        'deposit_pence',
        'capacity',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'price_pence' => 'integer',
            'deposit_pence' => 'integer',
            'capacity' => 'integer',
            'status' => CourseDateStatus::class,
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Bookings that occupy a place (anything not cancelled). */
    public function enrolledCount(): int
    {
        return $this->bookings()
            ->where('status', '!=', BookingStatus::Cancelled)
            ->count();
    }

    /**
     * @return Attribute<int, never>
     */
    protected function remainingPlaces(): Attribute
    {
        return Attribute::make(get: fn (): int => max(0, $this->capacity - $this->enrolledCount()));
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function effectivePricePence(): Attribute
    {
        return Attribute::make(get: fn (): ?int => $this->price_pence ?? $this->product?->price_pence);
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function effectiveDepositPence(): Attribute
    {
        return Attribute::make(get: fn (): ?int => $this->deposit_pence ?? $this->product?->deposit_pence);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->effective_price_pence === null
            ? 'Price on enquiry'
            : Money::formatPence($this->effective_price_pence));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function formattedDeposit(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->effective_deposit_pence === null
            ? null
            : Money::formatPence($this->effective_deposit_pence));
    }

    /**
     * Status as shown to customers/admin: an open course with no places
     * left presents as full without the admin having to flip it.
     *
     * @return Attribute<CourseDateStatus, never>
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::make(get: fn (): CourseDateStatus => $this->status === CourseDateStatus::Open && $this->remaining_places === 0
            ? CourseDateStatus::Full
            : $this->status);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function dateRangeLabel(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->ends_on === null || $this->ends_on->equalTo($this->starts_on)) {
                return $this->starts_on->format('j F Y');
            }

            return $this->starts_on->format('j')
                .'–'
                .$this->ends_on->format('j F Y');
        });
    }

    public function isBookable(): bool
    {
        return $this->status === CourseDateStatus::Open
            && $this->remaining_places > 0
            && $this->starts_on->isFuture()
            && $this->effective_deposit_pence !== null;
    }

    /**
     * Courses a customer can book right now.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcomingOpen(Builder $query): Builder
    {
        return $query
            ->where('status', CourseDateStatus::Open)
            ->whereDate('starts_on', '>=', now()->toDateString())
            ->orderBy('starts_on');
    }
}
