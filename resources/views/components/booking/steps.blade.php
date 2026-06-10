@props(['current' => 1, 'labels' => []])
{{-- Numbered step indicator for the booking flows. --}}
<ol class="flex items-center justify-center gap-2 sm:gap-4" aria-label="Booking progress">
    @foreach ($labels as $i => $label)
        @php $number = $i + 1; @endphp
        <li class="flex items-center gap-2">
            <span @class([
                'flex h-9 w-9 items-center justify-center font-display text-lg transition-colors',
                'bg-primary text-primary-foreground' => $number === $current,
                'bg-secondary text-secondary-foreground' => $number < $current,
                'border-2 border-input bg-card text-muted-foreground' => $number > $current,
            ])>
                @if ($number < $current)
                    <x-icon name="check" class="h-4 w-4" />
                @else
                    {{ $number }}
                @endif
            </span>
            <span @class([
                'hidden text-xs font-bold uppercase tracking-wide sm:inline',
                'text-secondary' => $number <= $current,
                'text-muted-foreground' => $number > $current,
            ])>{{ $label }}</span>
            @if (! $loop->last)
                <span class="h-0.5 w-6 bg-border sm:w-10" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
