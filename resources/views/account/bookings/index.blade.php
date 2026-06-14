@extends('layouts.app')

@section('title', 'My Bookings — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-site.page-hero title="My Bookings" subtitle="Your jumps, dates and balances." />

    <x-site.section>
        <x-account.nav />

        @if (session('account_status'))
            <div class="mt-6 border-l-4 border-primary bg-sky-bright/10 p-4 text-sm text-ink" role="status">{{ session('account_status') }}</div>
        @endif

        @forelse ($groups as $label => $bookings)
            <h2 class="{{ $loop->first ? 'mt-8' : 'mt-12' }} font-display text-2xl uppercase tracking-wide">{{ $label }}</h2>
            @foreach ($bookings as $booking)
                <x-account.booking-card :booking="$booking" />
            @endforeach
        @empty
            <p class="mt-8 text-muted-foreground">You don't have any bookings yet. Ready to take the leap?</p>
        @endforelse
    </x-site.section>
@endsection
