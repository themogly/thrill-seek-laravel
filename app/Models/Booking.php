<?php

namespace App\Models;

use App\Enums\BookingPaymentState;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Observers\BookingObserver;
use App\Support\Money;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property BookingStatus $status
 * @property Carbon|null $scheduled_at
 */
#[ObservedBy(BookingObserver::class)]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, LogsActivity;

    private const PRESENCE_CACHE_KEY = 'bookings.any';

    protected $fillable = [
        'reference',
        'name',
        'email',
        'phone',
        'product_id',
        'enquiry_id',
        'customer_id',
        'status',
        'scheduled_at',
        'tandem_date_id',
        'course_date_id',
        'price_pence',
        'customer_details',
        'notes',
        'reminder_sent_at',
        'balance_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'scheduled_at' => 'datetime',
            'price_pence' => 'integer',
            'customer_details' => 'array',
            'reminder_sent_at' => 'datetime',
            'balance_reminder_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            $booking->reference ??= self::generateReference();
        });

        // Assigning a tandem date schedules the booking; a booking
        // still awaiting a date becomes confirmed.
        static::saving(function (self $booking): void {
            if ($booking->isDirty('tandem_date_id') && $booking->tandem_date_id !== null) {
                $slot = TandemDate::find($booking->tandem_date_id);

                if ($slot !== null) {
                    $booking->scheduled_at = $slot->starts_at;

                    if ($booking->status === BookingStatus::PendingDate) {
                        $booking->status = BookingStatus::Confirmed;
                    }
                }
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'BK-'.Str::upper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Cheap "are there any bookings yet?" check used to auto-hide booking-only
     * admin UI (e.g. the calendar) while the system is empty. The flag is
     * cached and busted by the observer on create/delete, so the steady-state
     * read is a single cache hit, never a COUNT on every panel render.
     */
    public static function anyExistCached(): bool
    {
        return (bool) Cache::rememberForever(self::PRESENCE_CACHE_KEY, fn (): bool => self::exists());
    }

    public static function forgetPresenceCache(): void
    {
        Cache::forget(self::PRESENCE_CACHE_KEY);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'scheduled_at', 'price_pence', 'tandem_date_id', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Enquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /** @return BelongsTo<TandemDate, $this> */
    public function tandemDate(): BelongsTo
    {
        return $this->belongsTo(TandemDate::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<CourseDate, $this> */
    public function courseDate(): BelongsTo
    {
        return $this->belongsTo(CourseDate::class);
    }

    /**
     * Derived payment position (unpaid / deposit paid / paid in full).
     *
     * @return Attribute<BookingPaymentState, never>
     */
    protected function paymentState(): Attribute
    {
        return Attribute::make(get: function (): BookingPaymentState {
            if ($this->total_paid_pence === 0) {
                return BookingPaymentState::Unpaid;
            }

            return $this->hasOutstandingBalance()
                ? BookingPaymentState::DepositPaid
                : BookingPaymentState::PaidInFull;
        });
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return Attribute<int, never>
     */
    protected function totalPaidPence(): Attribute
    {
        return Attribute::make(get: fn (): int => (int) $this->payments
            ->where('status', PaymentStatus::Paid)
            ->sum('amount_pence'));
    }

    /**
     * @return Attribute<int, never>
     */
    protected function balanceDuePence(): Attribute
    {
        return Attribute::make(get: fn (): int => max(0, $this->price_pence - $this->total_paid_pence));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedBalanceDue(): Attribute
    {
        return Attribute::make(get: fn (): string => Money::formatPence($this->balance_due_pence));
    }

    /** The location of the jump/course, for emails and confirmations. */
    public function locationName(): ?string
    {
        return $this->courseDate->location->name
            ?? $this->tandemDate->location->name
            ?? null;
    }

    public function hasOutstandingBalance(): bool
    {
        return $this->balance_due_pence > 0;
    }

    /**
     * Whether the customer should be offered self-service balance payment: an
     * outstanding balance AND the booking is still open. A completed or cancelled
     * booking never shows a balance-due alarm or a "pay now" in the account — a
     * finished jump's settlement is admin-side. (Same balance calc, just gated.)
     */
    public function awaitingBalance(): bool
    {
        return $this->hasOutstandingBalance()
            && ! in_array($this->status, [BookingStatus::Completed, BookingStatus::Cancelled], true);
    }

    /**
     * SQL condition matching bookings whose paid payments do not yet cover
     * the price; shared by the scope and admin table filters.
     */
    public static function outstandingBalanceSql(): string
    {
        return "price_pence > coalesce((select sum(amount_pence) from payments where payments.booking_id = bookings.id and payments.status = 'paid'), 0)";
    }

    /**
     * Bookings whose paid payments do not yet cover the price.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithOutstandingBalance(Builder $query): Builder
    {
        return $query->whereRaw(self::outstandingBalanceSql());
    }
}
