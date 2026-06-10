<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnquiryStatus;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property EnquiryStatus $status
 */
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'name',
        'email',
        'phone',
        'product_id',
        'status',
        'preferred_date',
        'context',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnquiryStatus::class,
            'preferred_date' => 'date',
            'context' => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $enquiry): void {
            $enquiry->reference ??= self::generateReference();
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'GF-'.Str::upper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<EnquiryMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(EnquiryMessage::class)->orderBy('created_at')->orderBy('id');
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
