@extends('layouts.app')

@inject('page', 'App\Settings\CoachedPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" :image="$page->imageUrl($page->hero_image)" />

    {{-- INTRO: image bleeds off the left edge --}}
    <section class="overflow-hidden border-b-2 border-secondary">
        <div class="mx-auto grid max-w-7xl items-stretch gap-12 px-4 py-16 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div class="relative order-last lg:order-first lg:-ml-24" data-reveal>
                <img src="{{ $page->imageUrl($page->image) }}" alt="Advanced freefly coaching" class="h-full min-h-[24rem] w-full object-cover" loading="lazy" width="1280" height="896" />
            </div>
            <div data-reveal>
                <p class="flex items-center gap-3 text-sm font-bold uppercase tracking-[0.3em] text-primary">
                    <span class="inline-block h-0.5 w-10 bg-primary"></span>{{ $page->price_eyebrow }}
                </p>
                <h2 class="heading-rule mt-4 font-display text-5xl uppercase leading-[0.95] tracking-wide text-secondary md:text-7xl">{{ $page->heading }}</h2>
                <p class="mt-6 text-lg text-muted-foreground">
                    {{ $page->body }}
                </p>
                <ul class="mt-8 space-y-3">
                    @foreach ($page->skills as $b)
                        <li class="flex items-center gap-3 border-l-2 border-primary pl-3"><x-icon name="target" class="h-5 w-5 shrink-0 text-primary" />{{ $b }}</li>
                    @endforeach
                </ul>
                <x-ui.button href="#enquiry" size="lg" class="mt-10">
                    {{ $page->button_label }}
                </x-ui.button>
            </div>
        </div>
    </section>

    {{-- COACHING ENQUIRY --}}
    <section id="enquiry">
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-24">
            <x-site.section-heading :eyebrow="$page->enquiry_eyebrow" :title="$page->enquiry_title" :lead="$page->enquiry_lead" />
            <div class="mx-auto max-w-3xl">
                <livewire:coached-enquiry-form />
            </div>
        </div>
    </section>
@endsection
