<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName().' '.mb_strtoupper(fake()->randomLetter()).'.',
            'role' => fake()->randomElement(['Tandem jumper', 'AFF graduate', 'Coached skills']),
            'quote' => fake()->sentence(14),
            'excerpt' => null,
            'featured' => false,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function featured(): static
    {
        return $this->state(['featured' => true]);
    }
}
