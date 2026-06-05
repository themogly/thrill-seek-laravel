@extends('layouts.app')

@section('title', 'Hall of Fame — G-Force Skydiving')
@section('description', 'Celebrating our students and graduates — the G-Force Hall of Fame.')

@php
    $grads = [
        ['name' => 'James Carter', 'milestone' => 'A Licence — Spain 2024', 'img' => '/images/aff.jpg'],
        ['name' => 'Emma Walker', 'milestone' => 'First Tandem — Devon', 'img' => '/images/tandem.jpg'],
        ['name' => 'Mo Hassan', 'milestone' => 'B Licence achieved', 'img' => '/images/coached.jpg'],
        ['name' => 'Sophie Knight', 'milestone' => '100th jump', 'img' => '/images/hero-skydive.jpg'],
        ['name' => "Liam O'Connor", 'milestone' => 'Charity Tandem — £3,200 raised', 'img' => '/images/tandem.jpg'],
        ['name' => 'Rachel Stone', 'milestone' => 'AFF Levels 1–8 complete', 'img' => '/images/aff.jpg'],
        ['name' => 'Dan Pierce', 'milestone' => 'Freefly coach grade', 'img' => '/images/coached.jpg'],
        ['name' => 'Anya Patel', 'milestone' => 'Solo consolidation done', 'img' => '/images/hero-skydive.jpg'],
    ];
@endphp

@section('content')
    <x-site.page-hero title="Hall of Fame" subtitle="The students, graduates and coaches that make G-Force what it is." />
    <x-site.section>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($grads as $g)
                <div class="group relative aspect-[3/4] overflow-hidden rounded-2xl shadow-deep">
                    <img src="{{ $g['img'] }}" alt="{{ $g['name'] }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" loading="lazy" />
                    <div class="absolute inset-0 bg-gradient-to-t from-secondary via-secondary/40 to-transparent"></div>
                    <div class="absolute bottom-0 p-5 text-white">
                        <x-icon name="trophy" class="h-5 w-5 text-primary" />
                        <p class="mt-2 font-display text-xl uppercase">{{ $g['name'] }}</p>
                        <p class="text-sm opacity-90">{{ $g['milestone'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-site.section>
@endsection
