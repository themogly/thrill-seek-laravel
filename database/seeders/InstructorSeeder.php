<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        // Photos are bundled placeholder crops; the owner replaces them with
        // real portraits through the admin panel.
        $instructors = [
            ['name' => 'Joby Chadd', 'role' => 'Chief Instructor', 'bio' => 'Started skydiving in the military in 2004. British Skydiving and USPA rated, and able to sign off your A Licence.', 'photo' => '/images/instructors/jay.webp'],
            ['name' => 'Ricky', 'role' => 'AFF Instructor', 'bio' => 'Specialist in coaching new jumpers from first jump to A Licence.', 'photo' => '/images/instructors/ren.webp'],
            ['name' => 'Lucy Davies', 'role' => 'Tandem Instructor', 'bio' => 'Joined G-Force in Portugal in 2018 and completed her AFF in five days. Brings energy and safety to every jump.', 'photo' => '/images/instructors/lee.webp'],
        ];

        foreach ($instructors as $i => $data) {
            Instructor::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i + 1]),
            );
        }
    }
}
