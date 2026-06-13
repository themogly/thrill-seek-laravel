@extends('layouts.app')

@php
    $seoTitle = filled($article->seo_title) ? $article->seo_title : $article->title;
    $seoDescription = filled($article->seo_description)
        ? $article->seo_description
        : \Illuminate\Support\Str::limit(strip_tags($article->body), 150);
@endphp

@section('title', $seoTitle.' — G-Force Skydiving')
@section('description', $seoDescription)

@section('content')
    <x-site.page-hero :title="$article->title" :subtitle="$article->lead" :image="$article->featured_image_url" />
    <x-site.section>
        <article class="mx-auto max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground">
                {{ $article->published_at->format('j F Y') }}@if (filled($article->byline)) · {{ $article->byline }}@endif
            </p>

            <div class="mt-6 text-lg leading-8 text-foreground [&_a]:font-semibold [&_a]:text-primary [&_a]:underline [&_h2]:mb-3 [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-2xl [&_h2]:uppercase [&_h2]:text-secondary [&_li]:ml-1 [&_ol]:mb-5 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-5 [&_ul]:mb-5 [&_ul]:list-disc [&_ul]:pl-6">
                {!! $article->body !!}
            </div>

            @if ($course)
                {{-- Linked AFF course — availability read live, never cached. --}}
                <div class="mt-10 border-t-4 border-primary bg-secondary p-6 text-secondary-foreground lg:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">Linked AFF course</p>
                    <h2 class="mt-2 font-display text-3xl uppercase leading-none">{{ $course->date_range_label }}</h2>
                    <p class="mt-2 flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide">
                        <x-icon name="map-pin" class="h-4 w-4 text-sky-bright" /> {{ $course->location->name }}
                    </p>
                    <p class="mt-3 text-white/85">
                        {{ $course->remaining_places }} {{ \Illuminate\Support\Str::plural('place', $course->remaining_places) }} left
                        · {{ $course->formatted_deposit }} deposit
                    </p>
                    @if ($course->isBookable())
                        <x-ui.button :href="'/book/aff?course='.$course->id" size="lg" class="mt-5">
                            Book this course
                        </x-ui.button>
                    @else
                        <x-ui.button href="/aff" variant="outline" size="lg" class="mt-5">
                            See all courses
                        </x-ui.button>
                    @endif
                </div>
            @endif

            <div class="mt-10">
                <x-ui.button href="/news" variant="link">← All news</x-ui.button>
            </div>
        </article>
    </x-site.section>
@endsection
