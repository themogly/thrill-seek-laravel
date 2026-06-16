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
                 cards stack on mobile. The shared <x-site.instructor-card> renders the
                 full card here (square photo + navy band + chips + bio); the discipline
                 pages reuse the same partial with chips/bio hidden. --}}
            <div class="grid grid-cols-1 gap-px bg-secondary sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                @foreach ($instructors as $instructor)
                    <x-site.instructor-card :instructor="$instructor" />
                @endforeach
            </div>
        @endif
    </x-site.section>
@endsection
