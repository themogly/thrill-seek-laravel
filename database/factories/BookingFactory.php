<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'reference' => 'BK-'.Str::upper(Str::random(6)),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'product_id' => null,
            'enquiry_id' => null,
            'status' => BookingStatus::PendingDate,
            'scheduled_at' => null,
            'price_pence' => fake()->numberBetween(5000, 200000),
            'customer_details' => null,
            'notes' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ]);
    }
}
