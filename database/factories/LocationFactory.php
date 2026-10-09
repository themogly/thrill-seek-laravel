<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address_line' => fake()->streetAddress(),
            'town' => $name,
            'region' => fake()->randomElement(['Devon', 'Swansea', 'Midlands', 'Andalusia']),
            'postcode' => fake()->postcode(),
            'country' => 'United Kingdom',
            'lat' => fake()->latitude(50, 53),
            'lng' => fake()->longitude(-4, 0),
            'description' => fake()->sentence(10),
            'active' => true,
        ];
    }
}
