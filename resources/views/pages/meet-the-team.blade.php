@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->meet_the_team_seo_title)
@section('description', $pages->meet_the_team_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->meet_the_team_hero_title" :subtitle="$pages->meet_the_team_hero_subtitle" />

    <x-site.section>
        @if ($instructors->isEmpty())
            <p class="text-center text-lg text-muted-foreground">Our team will be introduced here soon.</p>
        @else
            {{-- Static responsive grid: every instructor visible, no desktop scroll;
                 cards stack on mobile. Each shows the longer bio and discipline tags.
                 Sibling aesthetic to the home team rail (gap-px on navy, white cards). --}}
            <div class="grid grid-cols-1 gap-px bg-secondary sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                @foreach ($instructors as $instructor)
                    <article class="group flex flex-col bg-background">
                        <div class="relative aspect-[4/5] overflow-hidden bg-secondary">
                            @if ($instructor->photo)
                                <img src="{{ $instructor->photo_url }}" alt="{{ $instructor->name }}"
                                     class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105 motion-reduce:transition-none"
                                     loading="lazy" width="800" height="1000" />
                            @else
                                {{-- Intentional editorial fallback: giant monogram on navy --}}
                                <div class="band-ink flex h-full w-full items-center justify-center">
                                    <span class="font-display text-[10rem] leading-none text-white/20">{{ \Illuminate\Support\Str::substr($instructor->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 border-t-4 border-primary bg-secondary/95 px-6 py-4 text-white">
                                <h2 class="font-display text-3xl uppercase leading-none">{{ $instructor->name }}</h2>
                                <p class="mt-1 text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">{{ $instructor->role }}</p>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col gap-4 border-2 border-t-0 border-border p-6">
                            <x-site.discipline-tags :disciplines="$instructor->disciplines" />
                            <p class="text-muted-foreground">{{ $instructor->bio }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-site.section>
@endsection
