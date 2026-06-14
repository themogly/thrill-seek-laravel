<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\CustomerFactory;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One record per unique email address, so repeat customers (AFF students
 * especially) have their enquiries and bookings in one place. Also the identity
 * for the passwordless customer account area (magic-link auth, `customer` guard)
 * — there are no stored passwords.
 *
 * @property Carbon|null $last_login_at
 * @property Carbon|null $erased_at
 */
class Customer extends Model implements Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use AuthenticatableTrait, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'last_login_at',
        'erased_at',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'erased_at' => 'datetime',
        ];
    }

    public function isErased(): bool
    {
        return $this->erased_at !== null;
    }

    /** @return HasMany<CustomerLoginLink, $this> */
    public function loginLinks(): HasMany
    {
        return $this->hasMany(CustomerLoginLink::class);
    }

    /** @return HasMany<Testimonial, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

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

    /** A customer can leave a review once they have a completed booking. */
    public function canLeaveReview(): bool
    {
        return $this->bookings()->where('status', BookingStatus::Completed)->exists();
    }

    /** Their most recent completed booking — used to pre-fill the review role. */
    public function latestCompletedBooking(): ?Booking
    {
        return $this->bookings()->where('status', BookingStatus::Completed)->with('product')->first();
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
