<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopItem>
 */
class ShopItemFactory extends Factory
{
    protected $model = ShopItem::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->word()),
            'price_label' => '£'.fake()->numberBetween(10, 300),
            'description' => fake()->sentence(6),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
