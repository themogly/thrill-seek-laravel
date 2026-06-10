<?php

declare(strict_types=1);

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
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
            ->whereBetween('starts_at', [
                $this->monthStart()->copy()->startOfMonth(),
                $this->monthStart()->copy()->endOfMonth(),
            ])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (TandemDate $slot): string => $slot->starts_at->format('Y-m-d'));
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
