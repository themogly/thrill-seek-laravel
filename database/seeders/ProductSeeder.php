<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tandem = Product::updateOrCreate(['slug' => 'tandem-skydive'], [
            'name' => 'Tandem Skydive',
            'type' => ProductType::Tandem,
            'summary' => 'Strap in with a pro and freefall from 15,000ft. Highest tandem in the UK.',
            'description' => 'A tandem skydive from 15,000ft strapped to a fully qualified G-Force instructor — around 60 seconds of freefall and 5–7 minutes of canopy flight.',
            'image' => '/images/tandem.webp',
            'page_path' => '/tandem',
            'price_pence' => 26000,
            'price_note' => 'Paid direct to G-Force',
            'show_from_price' => true,
            'weight_charges' => [
                ['band' => 'Up to 15st', 'charge' => 'Free'],
                ['band' => '15.1 – 16st', 'charge' => '£20'],
                ['band' => '16.1 – 17st', 'charge' => '£40'],
                ['band' => '17.1 – 18st', 'charge' => '£60'],
                ['band' => '18st+', 'charge' => 'Assessment required'],
            ],
            'featured_on_home' => true,
            'sort_order' => 1,
        ]);

        $addOns = [
            ['name' => 'Outside Camera', 'price_pence' => 14000, 'note' => 'Optional add-on', 'purchasable' => true],
            ['name' => 'HandCam', 'price_pence' => 10000, 'note' => 'Optional add-on', 'purchasable' => true],
            ['name' => 'P6 Third Party Insurance', 'price_pence' => 2473, 'note' => 'Paid on the day', 'purchasable' => false],
            ['name' => 'Rebooking Fee', 'price_pence' => 5000, 'note' => 'If you need to reschedule', 'purchasable' => false],
        ];

        foreach ($addOns as $i => $addOn) {
            $tandem->addOns()->updateOrCreate(
                ['name' => $addOn['name']],
                array_merge($addOn, ['sort_order' => $i + 1]),
            );
        }

        Product::updateOrCreate(['slug' => 'aff-course'], [
            'name' => 'AFF Course Levels 1–8',
            'type' => ProductType::Aff,
            'summary' => 'Become a licensed skydiver. Levels 1–8 with full kit and instruction.',
            'description' => 'The Accelerated Freefall course: full UK ground school then Levels 1–8 freefall progression with two instructors, run in Spain.',
            'image' => '/images/aff.webp',
            'page_path' => '/aff',
            'price_pence' => 175000,
            'deposit_pence' => 30000,
            'features' => ['All equipment', 'All instruction', 'Ground school', 'Levels 1–8'],
            'repeat_pricing' => [
                ['label' => 'Levels 1–3', 'value' => '£210 per jump'],
                ['label' => 'Levels 4–7', 'value' => '£140 per jump'],
            ],
            'highlight' => true,
            'featured_on_home' => true,
            'sort_order' => 2,
        ]);

        Product::updateOrCreate(['slug' => 'consolidation-jumps'], [
            'name' => 'Consolidation Jumps',
            'type' => ProductType::Aff,
            'summary' => '10 consolidation jumps to complete your A Licence.',
            'description' => 'Ten solo consolidation jumps with coach support, completing the journey to your A Licence.',
            'page_path' => '/aff',
            'price_pence' => 60000,
            'features' => ['10 jumps for A Licence', 'Solo progression', 'Coach support'],
            'sort_order' => 3,
        ]);

        Product::updateOrCreate(['slug' => 'coached-skills'], [
            'name' => 'Coached Skills',
            'type' => ProductType::Coaching,
            'summary' => '1-to-1 advanced flying coaching from world-class instructors.',
            'description' => '1-to-1 advanced coaching: belly, freefly, tracking and canopy skills with video debrief. Pricing is tailored per session.',
            'image' => '/images/coached.webp',
            'page_path' => '/coached',
            'price_pence' => 6000,
            'show_from_price' => true,
            'featured_on_home' => true,
            'sort_order' => 4,
        ]);
    }
}
