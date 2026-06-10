<?php

namespace Database\Seeders;

use App\Models\HallOfFameEntry;
use Illuminate\Database\Seeder;

class HallOfFameSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['name' => 'James Carter', 'milestone' => 'A Licence — Spain 2024', 'image' => '/images/aff.jpg'],
            ['name' => 'Emma Walker', 'milestone' => 'First Tandem — Devon', 'image' => '/images/tandem.jpg'],
            ['name' => 'Mo Hassan', 'milestone' => 'B Licence achieved', 'image' => '/images/coached.jpg'],
            ['name' => 'Sophie Knight', 'milestone' => '100th jump', 'image' => '/images/hero-skydive.jpg'],
            ['name' => "Liam O'Connor", 'milestone' => 'Charity Tandem — £3,200 raised', 'image' => '/images/tandem.jpg'],
            ['name' => 'Rachel Stone', 'milestone' => 'AFF Levels 1–8 complete', 'image' => '/images/aff.jpg'],
            ['name' => 'Dan Pierce', 'milestone' => 'Freefly coach grade', 'image' => '/images/coached.jpg'],
            ['name' => 'Anya Patel', 'milestone' => 'Solo consolidation done', 'image' => '/images/hero-skydive.jpg'],
        ];

        foreach ($entries as $i => $data) {
            HallOfFameEntry::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i + 1]),
            );
        }
    }
}
