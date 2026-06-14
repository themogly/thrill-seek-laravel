@extends('layouts.app')

@section('title', 'Payment cancelled — G-Force Skydiving')
@section('robots', 'noindex,follow')

@php
    $flow = request()->query('flow');
    $retryUrl = match ($flow) {
        'tandem' => '/book/tandem',
        'aff' => '/book/aff',
        default => null,
    };
@endphp

@section('content')
    <x-site.page-hero title="Payment Cancelled" subtitle="No charge was made — your card hasn't been touched." />
    <x-site.section>
        <div class="mx-auto max-w-xl text-center">
            <p class="text-lg text-muted-foreground">
                @if ($retryUrl)
                    Your place is released after a short while, so if you still want it, jump back in —
                    it only takes a minute.
                @else
                    Changed your mind or hit a problem? Get in touch and we'll help.
                @endif
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @if ($retryUrl)
                    <x-ui.button :href="$retryUrl" size="lg" >
                        Try again
                    </x-ui.button>
                @endif
                <x-ui.button href="/contact" size="lg" variant="outline" >
                    Contact us
                </x-ui.button>
            </div>
        </div>
    </x-site.section>
@endsection
