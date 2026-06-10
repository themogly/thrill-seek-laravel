<?php

namespace App\Observers;

use App\Models\CourseDate;

class CourseDateObserver
{
    /**
     * Every new course ships with the default reminder a week before the
     * start — the admin can edit, delete or add more on the course screen.
     */
    public function created(CourseDate $courseDate): void
    {
        $courseDate->reminders()->create([
            'days_before' => 7,
            'subject' => 'Your AFF course starts soon — what to bring',
            'body' => "Your course at {$courseDate->location->name} starts on {$courseDate->start_date->format('l j F Y')}.\n\n"
                ."A few things before the big week:\n"
                ."- Bring your logbook and any licence paperwork\n"
                ."- Comfortable clothes and trainers — kit is provided\n"
                ."- Arrive by 08:00 on the first morning for ground school\n\n"
                .'If you have any outstanding balance we will send a payment link separately.',
        ]);
    }
}
