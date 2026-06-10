<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    public function definition(): array
    {
        return [
            'image' => fake()->randomElement(['/images/tandem.jpg', '/images/aff.jpg', '/images/coached.jpg']),
            'alt_text' => 'Instagram post',
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
