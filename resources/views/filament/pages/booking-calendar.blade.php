<x-filament-panels::page>
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold">{{ $this->monthStart()->format('F Y') }}</h2>
        <div class="flex gap-2">
            <x-filament::button color="gray" wire:click="previousMonth">&larr; Previous</x-filament::button>
            <x-filament::button color="gray" wire:click="goToToday">Today</x-filament::button>
            <x-filament::button color="gray" wire:click="nextMonth">Next &rarr;</x-filament::button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <div class="grid min-w-[56rem] grid-cols-7 gap-px rounded-xl bg-gray-200 p-px dark:bg-white/10">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                <div class="bg-gray-50 px-2 py-1 text-center text-xs font-semibold uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                    {{ $dayName }}
                </div>
            @endforeach

            @foreach ($this->weeks() as $week)
                @foreach ($week as $day)
                    @php
                        $key = $day->format('Y-m-d');
                        $inMonth = $day->month === $this->monthStart()->month;
                        $bookings = $this->bookingsByDay->get($key, collect());
                        $slots = $this->slotsByDay->get($key, collect());
                    @endphp
                    <div @class([
                        'min-h-28 bg-white p-1.5 align-top dark:bg-gray-900',
                        'opacity-40' => ! $inMonth,
                    ])>
                        <div @class([
                            'text-xs font-semibold',
                            'text-primary-600 dark:text-primary-400' => $day->isToday(),
                            'text-gray-400' => ! $day->isToday(),
                        ])>
                            {{ $day->day }}
                        </div>

                        @foreach ($slots as $slot)
                            <div class="mt-1 rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                {{ $slot->starts_at->format('H:i') }} slot — {{ $slot->remaining_capacity }}/{{ $slot->capacity }} free
                            </div>
                        @endforeach

                        @foreach ($bookings as $booking)
                            <a
                                href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $booking]) }}"
                                @class([
                                    'mt-1 block truncate rounded px-1.5 py-0.5 text-[11px] font-medium',
                                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                                    'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $booking->status === \App\Enums\BookingStatus::Completed,
                                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => ! in_array($booking->status, [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Completed], true),
                                ])
                                title="{{ $booking->name }} — {{ $booking->product->name ?? 'Booking' }} ({{ $booking->status->getLabel() }})"
                            >
                                {{ $booking->scheduled_at->format('H:i') }} {{ $booking->name }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Bookings awaiting a date don't appear here — find them under
        <a href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl() }}" class="underline">Bookings</a>
        with the “Awaiting date” status.
    </p>
</x-filament-panels::page>
