<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = [
            ['name' => 'Joby', 'role' => 'Chief Instructor', 'bio' => 'Ex-military jumper with thousands of jumps and decades of teaching.'],
            ['name' => 'Ricky', 'role' => 'AFF Instructor', 'bio' => 'Specialist in coaching new jumpers from first jump to A licence.'],
            ['name' => 'Lucy', 'role' => 'Tandem Instructor', 'bio' => 'Bringing energy, safety and unforgettable experiences to every jump.'],
        ];

        foreach ($instructors as $i => $data) {
            Instructor::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i + 1]),
            );
        }
    }
}
