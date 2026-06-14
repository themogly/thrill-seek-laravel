<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property EnquiryStatus $status
 * @property Carbon|null $preferred_date
 * @property Carbon|null $last_customer_message_at
 */
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'reply_token',
        'name',
        'email',
        'phone',
        'product_id',
        'customer_id',
        'status',
        'preferred_date',
        'context',
        'read_at',
        'last_customer_message_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnquiryStatus::class,
            'preferred_date' => 'date',
            'context' => 'array',
            'read_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $enquiry): void {
            $enquiry->reference ??= self::generateReference();
            $enquiry->reply_token ??= Str::random(32);
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'GF-'.Str::upper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * The unguessable reply-to address customer replies are threaded by
     * (enquiry+{token}@{inbound_domain}). Null when no inbound domain is
     * configured, so callers fall back to the plain site address.
     */
    public function replyToAddress(): ?string
    {
        $domain = config('services.resend.inbound_domain');

        return $domain ? "enquiry+{$this->reply_token}@{$domain}" : null;
    }

    /** Resolve an enquiry from an inbound `to` address, by its reply token. */
    public static function findByReplyToken(string $token): ?self
    {
        return $token === '' ? null : self::where('reply_token', $token)->first();
    }

    /** Extract the token from an `enquiry+{token}@domain` address, if present. */
    public static function extractReplyToken(string $address): ?string
    {
        if (preg_match('/enquiry\+([A-Za-z0-9]+)@/', $address, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<EnquiryMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(EnquiryMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    /** @return HasOne<Booking, $this> */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->isUnread()) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
