<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property PaymentPurpose $purpose
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property array<string, mixed>|null $metadata
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'enquiry_id',
        'booking_id',
        'purpose',
        'method',
        'status',
        'amount_pence',
        'description',
        'reference',
        'metadata',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_pence' => 'integer',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Enquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(get: fn (): string => Money::formatPence($this->amount_pence));
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount_pence', 'method', 'purpose', 'reference', 'paid_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
