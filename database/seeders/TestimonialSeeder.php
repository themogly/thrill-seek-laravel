<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Sarah M.',
                'role' => 'Tandem jumper',
                'rating' => 5,
                'avatar' => '/images/instructors/ren.jpg',
                'photo' => '/images/hero-skydive.jpg',
                'quote' => "Absolutely life-changing. The team made me feel safe from the moment I arrived. I'll be back!",
                'excerpt' => 'Absolutely life-changing. The team made me feel safe from the moment I arrived.',
                'featured' => true,
            ],
            [
                'name' => 'Tom R.',
                'role' => 'AFF graduate',
                'rating' => 5,
                'avatar' => '/images/instructors/jay.jpg',
                'photo' => '/images/aff.jpg',
                'quote' => 'Did my AFF with G-Force in Spain. Best decision I ever made — incredible coaches and an unforgettable trip.',
                'excerpt' => 'Did my AFF with G-Force in Spain. Best decision I ever made — incredible coaches.',
                'featured' => true,
            ],
            [
                'name' => 'Priya K.',
                'role' => 'Tandem jumper',
                'rating' => 5,
                'photo' => '/images/tandem.jpg',
                'quote' => 'Tandem from 15,000ft. The view, the rush, the team. 10/10.',
                'excerpt' => null,
                'featured' => true,
            ],
            [
                'name' => 'Daniel H.',
                'role' => 'Coached skills',
                'rating' => 5,
                'photo' => '/images/coached.jpg',
                'quote' => "Joby's 1-to-1 coaching took my freefly skills from beginner to confident in a single weekend.",
            ],
            [
                'name' => 'Emma L.',
                'role' => 'Tandem jumper',
                'rating' => 4,
                'quote' => 'Did a charity tandem and raised over £1,000. The team supported every step.',
            ],
            [
                'name' => 'Mark B.',
                'role' => 'AFF graduate',
                'rating' => 5,
                'quote' => "Professional, friendly, safety-first. Wouldn't go anywhere else for my licence.",
            ],
            [
                'name' => 'Alice T.',
                'role' => 'Tandem jumper',
                'rating' => 5,
                'photo' => '/images/tandem.jpg',
                'quote' => 'The HandCam footage is amazing. Watching my face go from terror to pure joy — priceless.',
            ],
            [
                'name' => 'Liam O.',
                'role' => 'Coached skills',
                'rating' => 4,
                'quote' => 'Lucy is an incredible coach. Clear, patient, and genuinely cares about your progress.',
            ],
        ];

        foreach ($testimonials as $i => $data) {
            Testimonial::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i + 1, 'approved' => true]),
            );
        }
    }
}
