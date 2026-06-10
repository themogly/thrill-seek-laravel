<?php

namespace Database\Factories;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'role' => fake()->jobTitle(),
            'bio' => fake()->sentence(12),
            'photo' => null,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
