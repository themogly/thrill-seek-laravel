@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@php
    // The first featured testimonial becomes the hero feature; the rest fill
    // the masonry grid. No featured → everything goes in the grid.
    $featured = $testimonials->firstWhere('featured', true);
    $rest = $featured ? $testimonials->reject(fn ($t) => $t->is($featured))->values() : $testimonials;
@endphp

@section('title', $pages->testimonials_seo_title)
@section('description', $pages->testimonials_seo_description)

@php $ratingData = \App\Support\StructuredData::aggregateRating($testimonials); @endphp
@if ($ratingData)
    @push('json-ld')
        <x-seo.json-ld :data="$ratingData" />
    @endpush
@endif

@section('content')
    @if ($featured)
        {{-- The featured testimonial IS the page hero — no separate title band above
             it (avoids two stacked heroes). A small "Testimonials" eyebrow orients
             the visitor. --}}
        <section class="group relative isolate overflow-hidden border-b-4 border-primary bg-secondary text-white" data-reveal>
            @if ($featured->photo_url)
                <img src="{{ $featured->photo_url }}" alt="{{ $featured->name }}" width="1920" height="1080"
                     class="absolute inset-0 -z-10 h-full w-full object-cover" />
                <div class="absolute inset-0 -z-10 bg-photo-scrim"></div>
            @else
                <div class="band-ink absolute inset-0 -z-10"></div>
            @endif
            <div class="mx-auto max-w-4xl px-4 py-section lg:px-8 lg:py-section-lg">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">Testimonials</p>
                <span aria-hidden="true" class="mt-4 block font-display text-7xl leading-none text-sky-bright">&ldquo;</span>
                <blockquote class="-mt-6 font-display text-3xl uppercase leading-[1.05] tracking-wide md:text-5xl">
                    {{ $featured->home_quote }}
                </blockquote>
                <figcaption class="mt-8 flex items-center gap-4">
                    <x-site.avatar :name="$featured->name" :url="$featured->avatar_url" size="h-14 w-14" />
                    <span>
                        <cite class="block font-display text-2xl uppercase not-italic">{{ $featured->name }}</cite>
                        <span class="block text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">{{ $featured->role }}</span>
                        <x-site.stars :rating="$featured->rating" tone="dark" class="mt-1" />
                    </span>
                </figcaption>
            </div>
        </section>
    @else
        {{-- No featured quote to lead with — fall back to the standard page hero. --}}
        <x-site.page-hero :title="$pages->testimonials_hero_title" :subtitle="$pages->testimonials_hero_subtitle" />
    @endif

    <x-site.section>
        @if ($rest->isEmpty())
            @unless ($featured)
                <p class="text-center text-lg text-muted-foreground">No reviews yet.</p>
            @endunless
        @else
            {{-- Uniform equal-aspect grid: every tile is the same 4:5 height, so
                 short/monogram cards never stretch to a neighbour and there are no
                 in-tile voids. Photo-backed where a photo exists, intentional navy
                 monogram blocks where not. The hero above provides the visual break;
                 robust at any count and width (an incomplete final row just leaves
                 empty grid cells, never a stretched or stranded tile). --}}
            <div class="grid grid-cols-1 gap-px sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                @foreach ($rest as $t)
                    <x-site.photo-tile
                        :image="$t->photo_url"
                        :alt="$t->name"
                        :monogram="$t->name"
                        :width="700"
                        :height="875"
                        class="aspect-[4/5]"
                    >
                        <blockquote class="font-display text-base uppercase leading-snug tracking-wide">
                            {{ \Illuminate\Support\Str::limit($t->home_quote, 90) }}
                        </blockquote>
                        <div class="mt-3 border-t-2 border-primary pt-3">
                            <cite class="block text-sm font-bold uppercase tracking-widest not-italic">{{ $t->name }}</cite>
                            <span class="block text-xs uppercase tracking-[0.2em] text-white/70">{{ $t->role }}</span>
                            <x-site.stars :rating="$t->rating" tone="dark" class="mt-1.5" />
                        </div>
                    </x-site.photo-tile>
                @endforeach
            </div>
        @endif
    </x-site.section>
@endsection
