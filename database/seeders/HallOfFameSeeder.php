<?php

namespace Database\Seeders;

use App\Models\HallOfFameEntry;
use Illuminate\Database\Seeder;

class HallOfFameSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['name' => 'James Carter', 'milestone' => 'A Licence — Spain 2024', 'image' => '/images/aff.webp'],
            ['name' => 'Emma Walker', 'milestone' => 'First Tandem — Devon', 'image' => '/images/tandem.webp'],
            ['name' => 'Mo Hassan', 'milestone' => 'B Licence achieved', 'image' => '/images/coached.webp'],
            ['name' => 'Sophie Knight', 'milestone' => '100th jump', 'image' => '/images/hero-skydive.webp'],
            ['name' => "Liam O'Connor", 'milestone' => 'Charity Tandem — £3,200 raised', 'image' => '/images/tandem.webp'],
            ['name' => 'Rachel Stone', 'milestone' => 'AFF Levels 1–8 complete', 'image' => '/images/aff.webp'],
            ['name' => 'Dan Pierce', 'milestone' => 'Freefly coach grade', 'image' => '/images/coached.webp'],
            ['name' => 'Anya Patel', 'milestone' => 'Solo consolidation done', 'image' => '/images/hero-skydive.webp'],
        ];

        foreach ($entries as $i => $data) {
            HallOfFameEntry::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i + 1]),
            );
        }
    }
}
