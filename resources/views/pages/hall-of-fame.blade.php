@extends('layouts.app')

@section('title', 'Hall of Fame — G-Force Skydiving')
@section('description', 'Celebrating our students and graduates — the G-Force Hall of Fame.')

@section('content')
    <x-site.page-hero title="Hall of Fame" subtitle="The students, graduates and coaches that make G-Force what it is." />
    <x-site.section>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($entries as $g)
                <div class="group relative aspect-[3/4] overflow-hidden rounded-2xl shadow-deep">
                    <img src="{{ $g->image_url }}" alt="{{ $g->name }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" loading="lazy" />
                    <div class="absolute inset-0 bg-gradient-to-t from-secondary via-secondary/40 to-transparent"></div>
                    <div class="absolute bottom-0 p-5 text-white">
                        <x-icon name="trophy" class="h-5 w-5 text-primary" />
                        <p class="mt-2 font-display text-xl uppercase">{{ $g->name }}</p>
                        <p class="text-sm opacity-90">{{ $g->milestone }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-site.section>
@endsection
