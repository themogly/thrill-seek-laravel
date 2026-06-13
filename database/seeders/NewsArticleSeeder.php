<?php

namespace Database\Seeders;

use App\Models\CourseDate;
use App\Models\NewsArticle;
use Illuminate\Database\Seeder;

class NewsArticleSeeder extends Seeder
{
    public function run(): void
    {
        NewsArticle::updateOrCreate(
            ['slug' => 'welcome-to-the-2026-season'],
            [
                'title' => 'Welcome to the 2026 season',
                'lead' => 'Fresh tandem dates, new AFF courses and a few surprises — here’s what’s coming up.',
                'body' => '<p>The 2026 season is open! We’ve added jump days through the summer and a packed AFF calendar.</p><p>Whether it’s your first tandem or your A-licence, there’s never been a better time to get in the sky. Book early — popular weekends fill fast.</p>',
                'published' => true,
                'published_at' => now()->subWeek(),
                'byline' => 'By the G-Force team',
            ],
        );

        // Course-linked article — points at the next open AFF course if one exists.
        $course = CourseDate::upcomingOpen()->first();

        NewsArticle::updateOrCreate(
            ['slug' => 'new-aff-course-in-seville'],
            [
                'title' => 'New AFF course in Seville',
                'lead' => 'Sun, big skies and eight levels to your licence — our Spain course is back.',
                'body' => '<p>Our Seville AFF course pairs reliable weather with world-class facilities, so you progress fast.</p><p>Spaces are limited and they go quickly. Check the live dates below and reserve your place with a deposit.</p>',
                'published' => true,
                'published_at' => now()->subDays(3),
                'byline' => 'By the G-Force team',
                'course_date_id' => $course?->id,
            ],
        );
    }
}
