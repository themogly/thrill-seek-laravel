<?php

declare(strict_types=1);

namespace Tests\Feature\Courses;

use App\Filament\Resources\CourseDates\Pages\CreateCourseDate;
use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Filament\Resources\TandemDates\Pages\CreateTandemDate;
use App\Models\CourseDate;
use App\Models\Location;
use App\Models\Product;
use App\Models\TandemDate;
use App\Models\User;
use App\Support\DateClash;
use Livewire\Livewire;
use Tests\TestCase;

class DateRulesTest extends TestCase
{
    private Location $location;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->location = Location::factory()->create(['name' => 'Devon']);
        $this->product = Product::factory()->aff()->create();
    }

    public function test_courses_shorter_than_five_days_are_rejected(): void
    {
        Livewire::test(CreateCourseDate::class)
            ->fillForm([
                'product_id' => $this->product->id,
                'location_id' => $this->location->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-05', // 3 days inclusive
                'capacity' => 8,
                'status' => 'open',
            ])
            ->call('create')
            ->assertHasFormErrors(['end_date']);

        $this->assertSame(0, CourseDate::count());
    }

    public function test_five_day_courses_are_accepted_and_duration_is_computed(): void
    {
        Livewire::test(CreateCourseDate::class)
            ->fillForm([
                'product_id' => $this->product->id,
                'location_id' => $this->location->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-07', // exactly 5 days inclusive
                'capacity' => 8,
                'status' => 'open',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(5, CourseDate::sole()->duration_days);
    }

    public function test_a_tandem_date_cannot_land_inside_a_course_at_the_same_location(): void
    {
        CourseDate::factory()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-08',
        ]);

        Livewire::test(CreateTandemDate::class)
            ->fillForm([
                'location_id' => $this->location->id,
                'starts_at' => '2026-08-05 09:00',
                'capacity' => 6,
            ])
            ->call('create')
            ->assertHasFormErrors(['starts_at']);

        $this->assertSame(0, TandemDate::count());
    }

    public function test_the_same_day_at_a_different_location_is_fine(): void
    {
        CourseDate::factory()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-08',
        ]);

        $elsewhere = Location::factory()->create(['name' => 'Swansea']);

        Livewire::test(CreateTandemDate::class)
            ->fillForm([
                'location_id' => $elsewhere->id,
                'starts_at' => '2026-08-05 09:00',
                'capacity' => 6,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, TandemDate::count());
    }

    public function test_extending_a_course_over_an_existing_tandem_date_fails(): void
    {
        $course = CourseDate::factory()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-08',
        ]);

        TandemDate::factory()->create([
            'location_id' => $this->location->id,
            'starts_at' => '2026-08-10 09:00',
        ]);

        Livewire::test(EditCourseDate::class, ['record' => $course->getRouteKey()])
            ->fillForm(['end_date' => '2026-08-12'])
            ->call('save')
            ->assertHasFormErrors(['end_date']);

        $this->assertSame('2026-08-08', $course->refresh()->end_date->toDateString());
    }

    public function test_cancelled_courses_do_not_block_tandem_dates(): void
    {
        CourseDate::factory()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-08',
            'status' => 'cancelled',
        ]);

        Livewire::test(CreateTandemDate::class)
            ->fillForm([
                'location_id' => $this->location->id,
                'starts_at' => '2026-08-05 09:00',
                'capacity' => 6,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_clash_messages_name_the_offender(): void
    {
        $course = CourseDate::factory()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'start_date' => '2026-05-12',
            'end_date' => '2026-05-17',
        ]);
        $tandem = TandemDate::factory()->create([
            'location_id' => $this->location->id,
            'starts_at' => '2026-05-20 09:00',
        ]);

        $this->assertStringContainsString('12–17 May 2026', DateClash::describeCourse($course));
        $this->assertStringContainsString($this->product->name, DateClash::describeCourse($course));
        $this->assertStringContainsString('20 May 2026', DateClash::describeTandemDate($tandem));
    }
}
