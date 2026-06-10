<?php

namespace Database\Seeders;

use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'T-Shirt', 'price_label' => '£15 – £30', 'description' => 'Classic G-Force tee in multiple colours.'],
            ['name' => 'Tech Top', 'price_label' => '£30', 'description' => 'Lightweight technical top for under your suit.'],
            ['name' => 'Jumpsuit', 'price_label' => '£280', 'description' => 'Made-to-measure G-Force jumpsuit.'],
            ['name' => 'Buff', 'price_label' => '£10', 'description' => 'Multi-functional neck buff.'],
            ['name' => 'Gloves', 'price_label' => '£20', 'description' => 'Skydiving gloves for cold-weather jumping.'],
            ['name' => 'Day Sack', 'price_label' => '£30', 'description' => 'G-Force branded day sack.'],
            ['name' => 'Logbook', 'price_label' => '£15', 'description' => 'Official skydiving logbook.'],
            ['name' => 'USB', 'price_label' => '£12', 'description' => 'G-Force branded USB drive.'],
            ['name' => 'Water Bottle', 'price_label' => '£10', 'description' => 'Insulated G-Force water bottle.'],
        ];

        foreach ($items as $i => $data) {
            ShopItem::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i + 1]),
            );
        }
    }
}
