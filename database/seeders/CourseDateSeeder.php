<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CourseDate;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CourseDateSeeder extends Seeder
{
    public function run(): void
    {
        $course = Product::where('slug', 'aff-course')->first();

        if ($course === null || CourseDate::query()->exists()) {
            return;
        }

        $dates = [
            [
                'start_date' => now()->addMonth()->next('Monday')->toDateString(),
                'end_date' => now()->addMonth()->next('Monday')->addDays(4)->toDateString(),
                'location_id' => Location::where('slug', 'seville-spain')->value('id'),
                'capacity' => 8,
            ],
            [
                'start_date' => now()->addMonths(2)->next('Monday')->toDateString(),
                'end_date' => now()->addMonths(2)->next('Monday')->addDays(4)->toDateString(),
                'location_id' => Location::where('slug', 'seville-spain')->value('id'),
                'capacity' => 8,
            ],
        ];

        foreach ($dates as $date) {
            $course->courseDates()->create($date);
        }
    }
}
