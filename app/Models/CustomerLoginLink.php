<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A single-use, expiring magic-login token for the customer account area. Only
 * the SHA-256 hash of the token is stored; the plain token lives only in the
 * emailed link.
 *
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
class CustomerLoginLink extends Model
{
    protected $fillable = [
        'customer_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Mint a new link for a customer and return the PLAIN token (stored hashed).
     */
    public static function issueFor(Customer $customer, int $ttlMinutes = 20): string
    {
        $plain = Str::random(48);

        $customer->loginLinks()->create([
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return $plain;
    }

    /** Resolve a still-valid (unused, unexpired) link for a plain token. */
    public static function findValid(string $plain): ?self
    {
        return self::where('token_hash', hash('sha256', $plain))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function consume(): void
    {
        $this->forceFill(['used_at' => now()])->save();
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
