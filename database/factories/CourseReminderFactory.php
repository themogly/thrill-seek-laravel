<?php

namespace Database\Factories;

use App\Models\CourseDate;
use App\Models\CourseReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseReminder>
 */
class CourseReminderFactory extends Factory
{
    protected $model = CourseReminder::class;

    public function definition(): array
    {
        return [
            'course_date_id' => CourseDate::factory(),
            'days_before' => 7,
            'subject' => 'Your AFF course starts soon',
            'body' => fake()->paragraph(),
            'sent_at' => null,
        ];
    }
}
