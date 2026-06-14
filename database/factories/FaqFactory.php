<?php

namespace Database\Factories;

use App\Enums\FaqPage;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'page' => fake()->randomElement(FaqPage::cases()),
            'question' => rtrim(fake()->sentence(), '.').'?',
            'answer' => '<p>'.fake()->paragraph().'</p>',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    public function forPage(FaqPage $page): static
    {
        return $this->state(['page' => $page]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
