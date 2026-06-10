<?php

declare(strict_types=1);

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Pages\BookingCalendar;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BookingCalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_calendar_shows_bookings_and_slots_for_the_month(): void
    {
        $date = now()->addMonth()->startOfMonth()->setTime(9, 30);
        Booking::factory()->confirmed()->create(['name' => 'Cal Tester', 'scheduled_at' => $date]);
        AvailabilitySlot::factory()->create(['starts_at' => $date->copy()->setTime(13, 0), 'capacity' => 6]);

        Livewire::test(BookingCalendar::class, ['month' => $date->format('Y-m')])
            ->assertOk()
            ->assertSee('Cal Tester')
            ->assertSee('6/6 free');
    }

    public function test_month_navigation(): void
    {
        $component = Livewire::test(BookingCalendar::class, ['month' => '2026-06']);

        $component->call('nextMonth')->assertSet('month', '2026-07');
        $component->call('previousMonth')->assertSet('month', '2026-06');
        $component->call('goToToday')->assertSet('month', now()->format('Y-m'));
    }

    public function test_the_event_feed_groups_bookings_and_slots_by_day(): void
    {
        $first = now()->addMonth()->startOfMonth();
        $bookingA = Booking::factory()->confirmed()->create(['scheduled_at' => $first->copy()->setTime(9, 0)]);
        $bookingB = Booking::factory()->confirmed()->create(['scheduled_at' => $first->copy()->setTime(14, 0)]);
        $other = Booking::factory()->confirmed()->create(['scheduled_at' => $first->copy()->addDays(3)->setTime(10, 0)]);
        $slot = AvailabilitySlot::factory()->create(['starts_at' => $first->copy()->setTime(13, 0)]);

        $page = Livewire::test(BookingCalendar::class, ['month' => $first->format('Y-m')])->instance();

        $bookingsByDay = $page->bookingsByDay;
        $slotsByDay = $page->slotsByDay;

        $dayKey = $first->format('Y-m-d');
        $this->assertTrue($bookingsByDay->has($dayKey));
        $this->assertEqualsCanonicalizing(
            [$bookingA->id, $bookingB->id],
            $bookingsByDay->get($dayKey)->pluck('id')->all(),
        );
        $this->assertSame([$other->id], $bookingsByDay->get($first->copy()->addDays(3)->format('Y-m-d'))->pluck('id')->all());
        $this->assertSame([$slot->id], $slotsByDay->get($dayKey)->pluck('id')->all());

        foreach ($bookingsByDay->keys() as $key) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $key);
        }

        // The grid always covers the month in whole Monday-to-Sunday weeks.
        foreach ($page->weeks() as $week) {
            $this->assertCount(7, $week);
            $this->assertSame(1, $week[0]->dayOfWeekIso);
            $this->assertSame(7, $week[6]->dayOfWeekIso);
        }
    }

    public function test_calendar_page_renders_the_grid_with_the_compiled_panel_theme(): void
    {
        $response = $this->get('/admin/calendar');

        $response->assertOk();
        // The month grid markup is present…
        $response->assertSee('grid-cols-7');
        // …and the panel loads the Vite-compiled theme that contains those
        // utilities (the calendar rendered unstyled before the theme existed).
        $response->assertSee('build/assets/theme-');
    }

    public function test_cancelled_bookings_are_hidden_from_the_calendar(): void
    {
        $date = now()->addMonth()->startOfMonth()->setTime(9, 30);
        Booking::factory()->create([
            'name' => 'Cancelled Customer',
            'scheduled_at' => $date,
            'status' => BookingStatus::Cancelled,
        ]);

        Livewire::test(BookingCalendar::class, ['month' => $date->format('Y-m')])
            ->assertDontSee('Cancelled Customer');
    }
}
