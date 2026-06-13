@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->hall_of_fame_seo_title)
@section('description', $pages->hall_of_fame_seo_description)

@section('content')
    {{-- Compact photographic hero so the photo grid — the point of the page —
         starts high (replaces the old full-height flat-navy band). --}}
    <x-site.page-hero
        :title="$pages->hall_of_fame_hero_title"
        :subtitle="$pages->hall_of_fame_hero_subtitle"
        image="/images/hero-skydive.jpg"
        compact
    />
    <x-site.section>
        <div class="grid gap-px bg-secondary sm:grid-cols-2 lg:grid-cols-4" data-reveal>
            @foreach ($entries as $g)
                <x-site.photo-tile
                    :image="$g->image_url"
                    :alt="$g->name"
                    :monogram="$g->name"
                    icon="trophy"
                    :width="600"
                    :height="800"
                    class="aspect-[3/4]"
                >
                    <p class="mt-2 font-display text-xl uppercase leading-none">{{ $g->name }}</p>
                    <p class="text-sm text-white/90">{{ $g->milestone }}</p>
                    @if ($g->achieved_on || filled($g->note))
                        <p class="mt-1 text-xs uppercase tracking-wide text-white/70">
                            @if ($g->achieved_on){{ $g->achieved_on->format('M Y') }}@endif
                            @if ($g->achieved_on && filled($g->note)) · @endif
                            @if (filled($g->note)){{ $g->note }}@endif
                        </p>
                    @endif
                </x-site.photo-tile>
            @endforeach
        </div>
    </x-site.section>
@endsection
