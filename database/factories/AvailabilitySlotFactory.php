<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AvailabilitySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilitySlot>
 */
class AvailabilitySlotFactory extends Factory
{
    protected $model = AvailabilitySlot::class;

    public function definition(): array
    {
        return [
            'starts_at' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'capacity' => fake()->numberBetween(2, 12),
            'notes' => null,
        ];
    }
}
