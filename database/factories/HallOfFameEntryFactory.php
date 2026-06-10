<?php

namespace Database\Factories;

use App\Models\HallOfFameEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HallOfFameEntry>
 */
class HallOfFameEntryFactory extends Factory
{
    protected $model = HallOfFameEntry::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'milestone' => fake()->randomElement(['A Licence', 'First Tandem', '100th jump', 'B Licence achieved']),
            'image' => fake()->randomElement(['/images/aff.jpg', '/images/tandem.jpg', '/images/coached.jpg']),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
