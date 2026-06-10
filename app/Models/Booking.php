<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property BookingStatus $status
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'name',
        'email',
        'phone',
        'product_id',
        'enquiry_id',
        'status',
        'scheduled_at',
        'price_pence',
        'customer_details',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'scheduled_at' => 'datetime',
            'price_pence' => 'integer',
            'customer_details' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            $booking->reference ??= self::generateReference();
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'BK-'.Str::upper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
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

    public function hasOutstandingBalance(): bool
    {
        return $this->balance_due_pence > 0;
    }
}
