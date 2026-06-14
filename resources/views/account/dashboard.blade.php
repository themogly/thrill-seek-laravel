@extends('layouts.app')

@section('title', 'My Account — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-site.page-hero :title="'Hi, '.\Illuminate\Support\Str::of($customer->name)->before(' ')" subtitle="Welcome back to your account." />

    <x-site.section>
        <x-account.nav />

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            {{-- Next jump --}}
            <div class="border-2 border-border p-6">
                <h2 class="font-display text-2xl uppercase tracking-wide">Your next jump</h2>
                @if ($upcoming)
                    <p class="mt-4 text-xl font-bold text-ink">{{ $upcoming->product?->name ?? 'Skydive' }}</p>
                    <p class="mt-1 text-lg text-secondary">{{ $upcoming->scheduled_at?->format('l j F Y, g:ia') }}</p>
                    @if ($upcoming->locationName())
                        <p class="mt-1 text-muted-foreground">{{ $upcoming->locationName() }}</p>
                    @endif
                    @if (Route::has('account.bookings.show'))
                        <div class="mt-5">
                            <x-ui.button variant="outline" size="sm" :href="route('account.bookings.show', $upcoming)">View booking</x-ui.button>
                        </div>
                    @endif
                @else
                    <p class="mt-4 text-muted-foreground">No upcoming jump booked. When you book, it'll show here.</p>
                @endif
            </div>

            {{-- Outstanding balance --}}
            <div class="border-2 p-6 {{ $totalOutstandingPence > 0 ? 'border-primary bg-sky-bright/5' : 'border-border' }}">
                <h2 class="font-display text-2xl uppercase tracking-wide">Balance</h2>
                @if ($totalOutstandingPence > 0)
                    <p class="mt-4 text-muted-foreground">You have an outstanding balance of</p>
                    <p class="mt-1 font-display text-4xl text-primary">{{ $totalOutstandingLabel }}</p>
                    @if (Route::has('account.bookings'))
                        <div class="mt-5">
                            <x-ui.button :href="route('account.bookings')">Pay by card</x-ui.button>
                        </div>
                    @endif
                @else
                    <p class="mt-4 text-muted-foreground">You're all paid up. Nothing outstanding. 🪂</p>
                @endif
            </div>
        </div>

        {{-- Recent activity --}}
        <div class="mt-6 border-2 border-border p-6">
            <h2 class="font-display text-2xl uppercase tracking-wide">Recent payments</h2>
            @if ($recentPayments->isEmpty())
                <p class="mt-4 text-muted-foreground">No payments yet.</p>
            @else
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($recentPayments as $payment)
                        <li class="flex items-center justify-between py-3">
                            <span>
                                <span class="font-bold text-ink">{{ $payment->formatted_amount }}</span>
                                <span class="text-muted-foreground"> — {{ $payment->description ?? $payment->booking?->product?->name ?? 'Payment' }}</span>
                            </span>
                            <span class="text-sm text-muted-foreground">{{ $payment->paid_at?->format('j M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-site.section>
@endsection
