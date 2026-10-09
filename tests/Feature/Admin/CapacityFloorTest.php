<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Filament\Resources\TandemDates\Pages\EditTandemDate;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\TandemDate;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Places can't be cut below the people already booked — the date would be
 * overbooked with nothing on screen to say so.
 */
class CapacityFloorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_tandem_date_cannot_drop_below_its_bookings(): void
    {
        $slot = TandemDate::factory()->create(['capacity' => 4]);
        Booking::factory()->count(3)->confirmed()->create(['tandem_date_id' => $slot->id]);

        Livewire::test(EditTandemDate::class, ['record' => $slot->getRouteKey()])
            ->fillForm(['capacity' => 2])
            ->call('save')
            ->assertHasFormErrors(['capacity']);

        Livewire::test(EditTandemDate::class, ['record' => $slot->getRouteKey()])
            ->fillForm(['capacity' => 3])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_a_course_cannot_drop_below_its_students(): void
    {
        $course = CourseDate::factory()->create(['capacity' => 6]);
        Booking::factory()->count(2)->confirmed()->create(['course_date_id' => $course->id]);

        Livewire::test(EditCourseDate::class, ['record' => $course->getRouteKey()])
            ->fillForm(['capacity' => 1])
            ->call('save')
            ->assertHasFormErrors(['capacity']);
    }
}
