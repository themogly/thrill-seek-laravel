<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => ProductType::Tandem,
            'summary' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'image' => '/images/tandem.jpg',
            'page_path' => '/tandem',
            'price_pence' => fake()->numberBetween(5000, 200000),
            'deposit_pence' => null,
            'price_note' => null,
            'show_from_price' => false,
            'duration' => null,
            'features' => null,
            'weight_charges' => null,
            'repeat_pricing' => null,
            'highlight' => false,
            'featured_on_home' => false,
            'active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function tandem(): static
    {
        return $this->state(['type' => ProductType::Tandem, 'show_from_price' => true]);
    }

    public function aff(): static
    {
        return $this->state([
            'type' => ProductType::Aff,
            'deposit_pence' => 20000,
            'page_path' => '/aff',
        ]);
    }

    public function coaching(): static
    {
        return $this->state([
            'type' => ProductType::Coaching,
            'show_from_price' => true,
            'page_path' => '/coached',
        ]);
    }

    public function featuredOnHome(): static
    {
        return $this->state(['featured_on_home' => true]);
    }
}
