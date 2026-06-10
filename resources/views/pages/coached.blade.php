@extends('layouts.app')

@inject('page', 'App\Settings\CoachedPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" :image="$page->imageUrl($page->hero_image)" />
    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            <img src="{{ $page->imageUrl($page->image) }}" alt="Advanced freefly coaching" class="aspect-[4/5] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-primary">{{ $page->price_eyebrow }}</p>
                <h2 class="mt-3 font-display text-4xl uppercase tracking-wide text-secondary md:text-5xl">{{ $page->heading }}</h2>
                <p class="mt-6 text-lg text-muted-foreground">
                    {{ $page->body }}
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach ($page->skills as $b)
                        <li class="flex items-center gap-2"><x-icon name="target" class="h-5 w-5 text-primary" />{{ $b }}</li>
                    @endforeach
                </ul>
                <x-ui.button href="#enquiry" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                    {{ $page->button_label }}
                </x-ui.button>
            </div>
        </div>
    </x-site.section>

    {{-- COACHING ENQUIRY --}}
    <section id="enquiry" class="bg-muted">
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-24">
            <x-site.section-heading :eyebrow="$page->enquiry_eyebrow" :title="$page->enquiry_title" :lead="$page->enquiry_lead" />
            <div class="mx-auto max-w-3xl">
                <livewire:coached-enquiry-form />
            </div>
        </div>
    </section>
@endsection
