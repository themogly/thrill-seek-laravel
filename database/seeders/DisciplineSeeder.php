<?php

namespace Database\Seeders;

use App\Models\Discipline;
use App\Models\Instructor;
use Illuminate\Database\Seeder;

/**
 * The three disciplines the school teaches (matching the ProductType enum), plus
 * a starter assignment to the seeded instructors. The owner refines who teaches
 * what in the admin. Assignments match on first name so they survive a later
 * surname addition (e.g. "Joby" → "Joby Chadd" from the content-seed branch).
 */
class DisciplineSeeder extends Seeder
{
    public function run(): void
    {
        $disciplines = [
            ['name' => 'Tandem', 'slug' => 'tandem'],
            ['name' => 'AFF', 'slug' => 'aff'],
            ['name' => 'Coaching', 'slug' => 'coaching'],
        ];

        foreach ($disciplines as $i => $data) {
            Discipline::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['sort_order' => $i + 1]),
            );
        }

        // Starter assignments (idempotent via sync). The chief instructor teaches
        // everything; the others are tagged to their specialism.
        $assignments = [
            'Joby%' => ['tandem', 'aff', 'coaching'],
            'Ricky%' => ['aff', 'coaching'],
            'Lucy%' => ['tandem'],
        ];

        foreach ($assignments as $pattern => $slugs) {
            $instructor = Instructor::where('name', 'like', $pattern)->first();

            if (! $instructor) {
                continue;
            }

            $instructor->disciplines()->sync(
                Discipline::whereIn('slug', $slugs)->pluck('id'),
            );
        }
    }
}
