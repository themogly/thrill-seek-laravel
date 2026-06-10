<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourseDateStatus;
use App\Models\CourseDate;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseDate>
 */
class CourseDateFactory extends Factory
{
    protected $model = CourseDate::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+2 weeks', '+4 months');

        return [
            'product_id' => Product::factory()->aff(),
            'starts_on' => $start->format('Y-m-d'),
            'ends_on' => (clone $start)->modify('+4 days')->format('Y-m-d'),
            'location' => fake()->randomElement(['Seville, Spain', 'Ocaña, Spain', 'Devon, UK']),
            'price_pence' => null,
            'deposit_pence' => null,
            'capacity' => fake()->numberBetween(4, 10),
            'status' => CourseDateStatus::Open,
            'notes' => null,
        ];
    }

    public function full(): static
    {
        return $this->state(['status' => CourseDateStatus::Full]);
    }
}
