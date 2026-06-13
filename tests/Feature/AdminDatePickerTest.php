<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseDates\Pages\CreateCourseDate;
use App\Filament\Resources\TandemDates\Pages\CreateTandemDate;
use App\Models\User;
use App\Support\AdminDates;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDatePickerTest extends TestCase
{
    public function test_helper_builds_native_typeable_pickers(): void
    {
        // Native inputs accept keyboard entry; the JS picker is readonly/click-only.
        $this->assertTrue(AdminDates::date('x')->isNative());
        $this->assertTrue(AdminDates::dateTime('y')->isNative());
    }

    public function test_new_tandem_date_defaults_to_today(): void
    {
        $this->actingAs(User::factory()->create());

        $state = Livewire::test(CreateTandemDate::class)->get('data.starts_at');

        $this->assertNotNull($state);
        $this->assertTrue(Carbon::parse($state)->isToday());
    }

    public function test_new_course_defaults_to_a_five_day_range(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(CreateCourseDate::class);
        $start = Carbon::parse($component->get('data.start_date'));
        $end = Carbon::parse($component->get('data.end_date'));

        $this->assertTrue($start->isToday());
        // Inclusive 5-day course: start + 4 days.
        $this->assertSame(4, (int) $start->diffInDays($end));
    }

    public function test_changing_the_start_bumps_a_too_short_end(): void
    {
        $this->actingAs(User::factory()->create());

        $newStart = now()->addMonths(2)->startOfDay();

        $component = Livewire::test(CreateCourseDate::class)
            ->set('data.start_date', $newStart->toDateString());

        $end = Carbon::parse($component->get('data.end_date'));

        $this->assertSame(4, (int) $newStart->diffInDays($end));
    }
}
