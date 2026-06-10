<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VoucherStatus;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'code' => 'GV-'.Str::upper(Str::random(8)),
            'product_id' => null,
            'amount_pence' => 26000,
            'purchaser_name' => fake()->name(),
            'purchaser_email' => fake()->safeEmail(),
            'recipient_name' => fake()->firstName(),
            'message' => fake()->sentence(),
            'expires_at' => now()->addYear()->toDateString(),
            'status' => VoucherStatus::Active,
            'redeemed_at' => null,
            'booking_id' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()->toDateString()]);
    }
}
