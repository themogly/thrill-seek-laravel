<?php

declare(strict_types=1);

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Enums\CourseDateStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Location;
use App\Models\TandemDate;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class BookingCalendar extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Booking calendar';

    protected string $view = 'filament.resources.bookings.pages.booking-calendar';

    #[Url]
    public string $month = '';

    #[Url]
    public ?int $locationId = null;

    public function mount(): void
    {
        if ($this->month === '' || ! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay();
    }

    /**
     * Bookings in the visible month, grouped by day (Y-m-d).
     *
     * @return Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Booking>>
     */
    public function getBookingsByDayProperty(): Collection
    {
        return Booking::query()
            ->with('product')
            ->whereBetween('scheduled_at', [
                $this->monthStart()->copy()->startOfMonth(),
                $this->monthStart()->copy()->endOfMonth(),
            ])
            ->where('status', '!=', BookingStatus::Cancelled)
            ->when($this->locationId !== null, fn ($query) => $query->where(fn ($q) => $q
                ->whereHas('tandemDate', fn ($sub) => $sub->where('location_id', $this->locationId))
                ->orWhereHas('courseDate', fn ($sub) => $sub->where('location_id', $this->locationId))))
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (Booking $booking): string => (string) $booking->scheduled_at?->format('Y-m-d'));
    }

    /**
     * Tandem dates in the visible month, grouped by day (Y-m-d).
     *
     * @return Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, TandemDate>>
     */
    public function getSlotsByDayProperty(): Collection
    {
        return TandemDate::query()
            ->with('location')
            ->whereBetween('starts_at', [
                $this->monthStart()->copy()->startOfMonth(),
                $this->monthStart()->copy()->endOfMonth(),
            ])
            ->when($this->locationId !== null, fn ($query) => $query->where('location_id', $this->locationId))
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (TandemDate $slot): string => $slot->starts_at->format('Y-m-d'));
    }

    /**
     * AFF courses overlapping the visible month — each course appears on
     * every day of its range so multi-day spans render across the grid.
     *
     * @return Collection<int|string, Collection<int, CourseDate>>
     */
    public function getCoursesByDayProperty(): Collection
    {
        $monthStart = $this->monthStart()->copy()->startOfMonth();
        $monthEnd = $this->monthStart()->copy()->endOfMonth();

        $courses = CourseDate::query()
            ->with(['product', 'location'])
            ->where('status', '!=', CourseDateStatus::Cancelled)
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->when($this->locationId !== null, fn ($query) => $query->where('location_id', $this->locationId))
            ->orderBy('start_date')
            ->get();

        $byDay = collect();

        foreach ($courses as $course) {
            $day = $course->start_date->copy();

            while ($day->lte($course->end_date)) {
                $key = $day->format('Y-m-d');
                $byDay[$key] = ($byDay[$key] ?? collect())->push($course);
                $day->addDay();
            }
        }

        return $byDay;
    }

    /** @return array<int, string> */
    public function getLocationOptionsProperty(): array
    {
        return Location::orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * The weeks (arrays of Carbon days) that make up the calendar grid,
     * padded to full Monday-to-Sunday weeks.
     *
     * @return array<int, array<int, Carbon>>
     */
    public function weeks(): array
    {
        $start = $this->monthStart()->copy()->startOfMonth()->startOfWeek();
        $end = $this->monthStart()->copy()->endOfMonth()->endOfWeek();

        $weeks = [];
        $day = $start->copy();

        while ($day <= $end) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = $day->copy();
                $day->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }
}
