<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One record per unique email address, so repeat customers (AFF students
 * especially) have their enquiries and bookings in one place.
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
    ];

    /** @return HasMany<Enquiry, $this> */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class)->latest();
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    /**
     * Find or create the customer for an email address, filling in any
     * details we did not previously have.
     */
    public static function resolve(string $email, string $name, ?string $phone = null): self
    {
        $customer = self::firstOrCreate(
            ['email' => mb_strtolower($email)],
            ['name' => $name, 'phone' => $phone],
        );

        if ($customer->phone === null && $phone !== null) {
            $customer->update(['phone' => $phone]);
        }

        return $customer;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function totalSpent(): Attribute
    {
        return Attribute::make(get: fn (): string => Money::formatPence(
            (int) Payment::whereIn('booking_id', $this->bookings()->pluck('id'))
                ->where('status', PaymentStatus::Paid)
                ->sum('amount_pence'),
        ));
    }
}
