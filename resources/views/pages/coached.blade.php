@extends('layouts.app')

@section('title', 'Coached Advanced Flying Skills — G-Force Skydiving')
@section('description', '1-to-1 advanced skydiving coaching from £60. Belly, freefly, tracking and canopy skills.')

@php
    $skills = ['Belly flying & RW', 'Freefly progression', 'Tracking & angle flying', 'Canopy piloting', 'Video debrief included'];
@endphp

@section('content')
    <x-site.page-hero title="Coached Skills" subtitle="1-to-1 advanced flying coaching to take your skydiving to the next level." />
    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            <img src="/images/coached.jpg" alt="Advanced freefly coaching" class="aspect-[4/5] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-primary">From £60 per session</p>
                <h2 class="mt-3 font-display text-4xl uppercase tracking-wide text-secondary md:text-5xl">Fly Better. Fly Smarter.</h2>
                <p class="mt-6 text-lg text-muted-foreground">
                    Whether you're chasing your B licence, working on freefly, tracking or canopy control, our coaches give
                    you focused 1-to-1 attention with video debrief and a personalised plan.
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach ($skills as $b)
                        <li class="flex items-center gap-2"><x-icon name="target" class="h-5 w-5 text-primary" />{{ $b }}</li>
                    @endforeach
                </ul>
                <x-ui.button href="/contact" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                    Book a session
                </x-ui.button>
            </div>
        </div>
    </x-site.section>
@endsection
