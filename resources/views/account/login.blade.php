@extends('layouts.app')

@section('title', 'My Account — G-Force Skydiving')
@section('description', 'Sign in to view your bookings, pay any balance and see your messages.')
@section('robots', 'noindex, follow')

@section('content')
    <x-site.page-hero title="My Account" subtitle="View your bookings, pay any balance and read your messages." />

    <x-site.section>
        <div class="mx-auto max-w-md">
            @if (session('account_status'))
                <div class="mb-6 border-l-4 border-primary bg-sky-bright/10 p-4 text-sm text-ink" role="status">
                    {{ session('account_status') }}
                </div>
            @endif
            @if (session('account_error'))
                <div class="mb-6 border-l-4 border-destructive bg-destructive/10 p-4 text-sm text-ink" role="alert">
                    {{ session('account_error') }}
                </div>
            @endif

            <h2 class="font-display text-3xl uppercase tracking-wide">Sign in</h2>
            <p class="mt-3 text-muted-foreground">
                Enter the email you booked with and we'll send you a secure sign-in link — no password needed.
            </p>

            <form method="POST" action="{{ route('account.login.send') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-bold uppercase tracking-widest text-secondary">Email address</label>
                    <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}"
                           class="mt-2 w-full border-2 border-border bg-background px-4 py-3 text-ink focus:border-primary focus:outline-none" />
                    @error('email')
                        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <x-ui.button type="submit" class="w-full">Email me a sign-in link</x-ui.button>
            </form>

            <p class="mt-6 text-xs text-muted-foreground">
                Only people who have booked with us have an account. Haven't booked yet?
                <a href="{{ route('tandem') }}" class="font-bold text-primary hover:underline">See our jumps</a>.
            </p>
        </div>
    </x-site.section>
@endsection
