@extends('layouts.app')

@section('title', 'My Bookings — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@php use App\Support\Money; @endphp

@section('content')
    <x-site.page-hero title="My Bookings" subtitle="Your jumps, dates and balances." />

    <x-site.section>
        <x-account.nav />

        @if (session('account_status'))
            <div class="mt-6 border-l-4 border-primary bg-sky-bright/10 p-4 text-sm text-ink" role="status">{{ session('account_status') }}</div>
        @endif

        <h2 class="mt-8 font-display text-2xl uppercase tracking-wide">Upcoming</h2>
        @forelse ($upcoming as $booking)
            <x-account.booking-card :booking="$booking" />
        @empty
            <p class="mt-4 text-muted-foreground">No upcoming jumps booked.</p>
        @endforelse

        @if ($past->isNotEmpty())
            <h2 class="mt-12 font-display text-2xl uppercase tracking-wide">Past &amp; awaiting</h2>
            @foreach ($past as $booking)
                <x-account.booking-card :booking="$booking" />
            @endforeach
        @endif
    </x-site.section>
@endsection
