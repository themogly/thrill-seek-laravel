<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\TandemDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TandemDate>
 */
class TandemDateFactory extends Factory
{
    protected $model = TandemDate::class;

    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'capacity' => fake()->numberBetween(2, 12),
            'notes' => null,
        ];
    }
}
