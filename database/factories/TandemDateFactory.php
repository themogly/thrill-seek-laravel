<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\TandemDate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
            // A sensible daytime slot time, never a random small-hours time.
            'starts_at' => Carbon::instance(fake()->dateTimeBetween('+1 week', '+3 months'))
                ->setTime(fake()->randomElement([9, 10, 11, 13, 14]), 0),
            'capacity' => fake()->numberBetween(2, 12),
            'notes' => null,
        ];
    }
}
