<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /** The operating locations named in the current site content. */
    public function run(): void
    {
        $locations = [
            ['name' => 'Devon', 'region' => 'Devon', 'country' => 'United Kingdom', 'description' => 'Our home dropzone in the South West.'],
            ['name' => 'Swansea', 'region' => 'Swansea', 'country' => 'United Kingdom', 'description' => 'Coastal jumps over the Gower peninsula.'],
            ['name' => 'Hinton Midlands', 'region' => 'Midlands', 'country' => 'United Kingdom', 'description' => 'Our central England dropzone.'],
            ['name' => 'Seville, Spain', 'region' => 'Andalusia', 'country' => 'Spain', 'description' => 'Sun-soaked AFF course venue with reliable weather.'],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['slug' => str($location['name'])->slug()->toString()],
                $location,
            );
        }
    }
}
