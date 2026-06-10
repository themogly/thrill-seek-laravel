<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VoucherStatus;
use App\Support\Money;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A gift voucher, usually for a tandem skydive. "Expired" is derived from
 * the expiry date at display time; the stored status only tracks the
 * lifecycle the admin controls (active / redeemed / cancelled).
 *
 * @property VoucherStatus $status
 * @property Carbon $expires_at
 */
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'product_id',
        'amount_pence',
        'purchaser_name',
        'purchaser_email',
        'recipient_name',
        'message',
        'expires_at',
        'status',
        'redeemed_at',
        'booking_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_pence' => 'integer',
            'expires_at' => 'date',
            'status' => VoucherStatus::class,
            'redeemed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $voucher): void {
            $voucher->code ??= self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'GV-'.Str::upper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isRedeemable(): bool
    {
        return $this->status === VoucherStatus::Active && ! $this->expires_at->isPast();
    }

    /**
     * Status as shown to the admin, deriving "expired" from the date.
     *
     * @return Attribute<VoucherStatus, never>
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::make(get: fn (): VoucherStatus => $this->status === VoucherStatus::Active && $this->expires_at->isPast()
            ? VoucherStatus::Expired
            : $this->status);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(get: fn (): string => Money::formatPence($this->amount_pence));
    }
}
