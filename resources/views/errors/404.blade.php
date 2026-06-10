@extends('layouts.app')

@section('title', 'Page not found — G-Force Skydiving')

@section('content')
    <x-site.page-hero title="Lost in Freefall" subtitle="That page doesn't exist or has moved — but the ground is this way." />
    <x-site.section>
        <div class="mx-auto max-w-xl text-center">
            <p class="font-display text-[10rem] uppercase leading-none text-primary">404</p>
            <p class="mt-4 text-lg text-muted-foreground">Here's where most people want to land:</p>
            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                <x-ui.button href="/book/tandem" class="w-full">Book a tandem</x-ui.button>
                <x-ui.button href="/aff" variant="outline" class="w-full">AFF courses</x-ui.button>
                <x-ui.button href="/contact" variant="outline" class="w-full">Contact us</x-ui.button>
            </div>
        </div>
    </x-site.section>
@endsection
