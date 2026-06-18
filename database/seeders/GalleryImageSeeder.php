<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use Illuminate\Database\Seeder;

class GalleryImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            '/images/tandem.webp',
            '/images/aff.webp',
            '/images/coached.webp',
            '/images/hero-skydive.webp',
            '/images/tandem.webp',
            '/images/aff.webp',
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
