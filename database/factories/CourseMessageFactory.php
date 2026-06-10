<?php

namespace Database\Factories;

use App\Models\CourseDate;
use App\Models\CourseMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseMessage>
 */
class CourseMessageFactory extends Factory
{
    protected $model = CourseMessage::class;

    public function definition(): array
    {
        return [
            'course_date_id' => CourseDate::factory(),
            'user_id' => null,
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'recipients' => [],
            'source' => 'manual',
        ];
    }
}
