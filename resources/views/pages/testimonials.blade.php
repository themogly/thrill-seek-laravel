@extends('layouts.app')

@section('title', 'Testimonials — G-Force Skydiving')
@section('description', 'Real reviews from G-Force tandem students and AFF graduates.')

@php
    $items = [
        ['name' => 'Sarah M.', 'role' => 'Tandem jumper', 'text' => "Absolutely life-changing. The team made me feel safe from the moment I arrived. I'll be back!"],
        ['name' => 'Tom R.', 'role' => 'AFF graduate', 'text' => 'Did my AFF with G-Force in Spain. Best decision I ever made — incredible coaches and an unforgettable trip.'],
        ['name' => 'Priya K.', 'role' => 'Tandem jumper', 'text' => 'Tandem from 15,000ft. The view, the rush, the team. 10/10.'],
        ['name' => 'Daniel H.', 'role' => 'Coached skills', 'text' => "Joby's 1-to-1 coaching took my freefly skills from beginner to confident in a single weekend."],
        ['name' => 'Emma L.', 'role' => 'Tandem jumper', 'text' => 'Did a charity tandem and raised over £1,000. The team supported every step.'],
        ['name' => 'Mark B.', 'role' => 'AFF graduate', 'text' => "Professional, friendly, safety-first. Wouldn't go anywhere else for my licence."],
        ['name' => 'Alice T.', 'role' => 'Tandem jumper', 'text' => 'The HandCam footage is amazing. Watching my face go from terror to pure joy — priceless.'],
        ['name' => 'Liam O.', 'role' => 'Coached skills', 'text' => 'Lucy is an incredible coach. Clear, patient, and genuinely cares about your progress.'],
    ];
@endphp

@section('content')
    <x-site.page-hero title="Testimonials" subtitle="Real stories from the people who've jumped with us." />
    <x-site.section>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $t)
                <figure class="rounded-2xl border bg-card p-6 shadow-sm transition-shadow hover:shadow-glow">
                    <x-icon name="quote" class="h-8 w-8 text-primary" />
                    <blockquote class="mt-4 text-base">{{ $t['text'] }}</blockquote>
                    <figcaption class="mt-4">
                        <p class="font-display uppercase text-secondary">{{ $t['name'] }}</p>
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ $t['role'] }}</p>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </x-site.section>
@endsection
