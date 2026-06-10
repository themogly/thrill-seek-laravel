<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAddOn>
 */
class ProductAddOnFactory extends Factory
{
    protected $model = ProductAddOn::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'price_pence' => fake()->numberBetween(1000, 20000),
            'note' => 'Optional add-on',
            'purchasable' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
