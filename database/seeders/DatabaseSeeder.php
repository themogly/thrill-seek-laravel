<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // A known-credential admin only ever exists on a developer's machine.
        // Servers get theirs from `php artisan make:filament-user`.
        if (app()->environment('local')) {
            $this->call(DevAdminSeeder::class);
        }

        $this->call([
            InstructorSeeder::class,
            DisciplineSeeder::class,
            TestimonialSeeder::class,
            HallOfFameSeeder::class,
            ShopItemSeeder::class,
            GalleryImageSeeder::class,
            ProductSeeder::class,
            EmailTemplateSeeder::class,
            LocationSeeder::class,
            CourseDateSeeder::class,
            NewsArticleSeeder::class,
            NewsletterCampaignSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
