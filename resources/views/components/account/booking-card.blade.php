@props(['booking'])
@php use App\Support\Money; @endphp
<div class="mt-4 flex flex-col gap-4 border-2 border-border p-5 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-lg font-bold text-ink">{{ $booking->product?->name ?? 'Skydive' }}</p>
        <p class="mt-1 text-secondary">
            {{ $booking->scheduled_at?->format('l j F Y, g:ia') ?? 'Date to be confirmed' }}
            @if ($booking->locationName()) · {{ $booking->locationName() }} @endif
        </p>
        <p class="mt-1 text-sm uppercase tracking-widest text-muted-foreground">{{ $booking->status->getLabel() }}</p>
    </div>
    <div class="sm:text-right">
        @if ($booking->hasOutstandingBalance())
            <p class="text-sm text-muted-foreground">Balance due</p>
            <p class="font-display text-2xl text-primary">{{ $booking->formatted_balance_due }}</p>
        @else
            <p class="font-bold uppercase tracking-widest text-secondary">Paid in full</p>
        @endif
        <div class="mt-3">
            <x-ui.button variant="outline" size="sm" :href="route('account.bookings.show', $booking)">View details</x-ui.button>
        </div>
    </div>
</div>
