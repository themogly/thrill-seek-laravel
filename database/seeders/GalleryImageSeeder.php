<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GalleryImage;
use Illuminate\Database\Seeder;

class GalleryImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            '/images/tandem.jpg',
            '/images/aff.jpg',
            '/images/coached.jpg',
            '/images/hero-skydive.jpg',
            '/images/tandem.jpg',
            '/images/aff.jpg',
        ];

        if (GalleryImage::query()->exists()) {
            return;
        }

        foreach ($images as $i => $image) {
            GalleryImage::create([
                'image' => $image,
                'alt_text' => 'Instagram post',
                'sort_order' => $i + 1,
            ]);
        }
    }
}
