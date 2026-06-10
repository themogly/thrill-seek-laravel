<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CourseDateStatus;
use App\Observers\CourseDateObserver;
use App\Support\Money;
use Database\Factories\CourseDateFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A scheduled AFF course: a multi-day date range (minimum 5 days) at a
 * location with limited places. Price and deposit fall back to the linked
 * product when no override is set.
 *
 * @property CourseDateStatus $status
 * @property Carbon $start_date
 * @property Carbon $end_date
 */
#[ObservedBy(CourseDateObserver::class)]
class CourseDate extends Model
{
    /** @use HasFactory<CourseDateFactory> */
    use HasFactory;

    /** AFF courses run for at least this many days (inclusive). */
    public const MIN_DURATION_DAYS = 5;

    protected $fillable = [
        'product_id',
        'location_id',
        'start_date',
        'end_date',
        'price_pence',
        'deposit_pence',
        'capacity',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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

    /** @return HasMany<CourseMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(CourseMessage::class)->latest();
    }

    /** @return HasMany<CourseReminder, $this> */
    public function reminders(): HasMany
    {
        return $this->hasMany(CourseReminder::class)->orderBy('days_before', 'desc');
    }

    /**
     * Students who should receive course communications: everyone holding a
     * place except cancelled bookings and unpaid checkout holds.
     *
     * @return Collection<int, Booking>
     */
    public function messageableBookings(): Collection
    {
        return $this->bookings()
            ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::PendingPayment])
            ->get();
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
     * Inclusive length of the course in days.
     *
     * @return Attribute<int, never>
     */
    protected function durationDays(): Attribute
    {
        return Attribute::make(get: fn (): int => (int) $this->start_date->diffInDays($this->end_date) + 1);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function dateRangeLabel(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->end_date->equalTo($this->start_date)) {
                return $this->start_date->format('j F Y');
            }

            return $this->start_date->format('j')
                .'–'
                .$this->end_date->format('j F Y');
        });
    }

    public function isBookable(): bool
    {
        return $this->status === CourseDateStatus::Open
            && $this->remaining_places > 0
            && $this->start_date->isFuture()
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
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date');
    }
}
