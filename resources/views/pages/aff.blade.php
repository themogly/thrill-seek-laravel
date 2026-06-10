@extends('layouts.app')

@inject('page', 'App\Settings\AffPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" :image="$page->imageUrl($page->hero_image)" />

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            <img src="{{ $page->imageUrl($page->intro_image) }}" alt="AFF training" class="aspect-[4/5] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
            <div>
                <x-site.section-heading :eyebrow="$page->intro_eyebrow" :title="$page->intro_title" :lead="$page->intro_lead" />
                <ul class="space-y-3">
                    @foreach ($page->bullets as $b)
                        <li class="flex items-start gap-2"><x-icon name="check" class="mt-1 h-5 w-5 flex-shrink-0 text-primary" /><span>{{ $b }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-site.section>

    {{-- TRUST STRIP --}}
    <section class="border-y bg-card">
        <div class="mx-auto max-w-7xl px-4 py-14 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-primary">{{ $page->trust_eyebrow }}</p>
                <h2 class="mt-2 font-display text-3xl uppercase tracking-wide text-secondary md:text-4xl">{{ $page->trust_title }}</h2>
                <p class="mt-4 text-muted-foreground">
                    {{ $page->trust_body }}
                </p>
            </div>
            <x-site.trust-grid />
        </div>
    </section>

    <section class="bg-muted">
        <div class="mx-auto max-w-7xl px-4 py-20 lg:px-8">
            <x-site.section-heading :eyebrow="$page->pricing_eyebrow" :title="$page->pricing_title" />
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($products as $p)
                    <x-site.price-card :title="$p->name" :price="$p->formatted_price" :features="$p->features ?? []" :highlight="$p->highlight" />
                @endforeach
            </div>
            @foreach ($products->filter(fn ($p) => filled($p->repeat_pricing)) as $p)
                <div class="mt-6 rounded-2xl border bg-card p-6">
                    <h3 class="font-display text-xl text-secondary">{{ $page->repeat_pricing_heading }}</h3>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($p->repeat_pricing as $row)
                            <div class="rounded-md bg-muted p-3"><span class="font-semibold">{{ $row['label'] }}:</span> {{ $row['value'] }}</div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- UPCOMING COURSES --}}
    <x-site.section id="courses">
        <x-site.section-heading :eyebrow="$page->courses_eyebrow" :title="$page->courses_title" :lead="$page->courses_lead" />
        @if ($courseDates->isEmpty())
            <div class="rounded-2xl border bg-card p-8 text-center">
                <p class="text-lg text-muted-foreground">{{ $page->courses_empty_text }}</p>
                <x-ui.button href="/contact" class="mt-6 bg-primary text-primary-foreground hover:bg-primary/90">Get in touch</x-ui.button>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($courseDates as $course)
                    <div class="flex flex-col justify-between gap-6 rounded-2xl border bg-card p-6 shadow-sm transition-shadow hover:shadow-glow sm:p-8">
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-display text-3xl uppercase text-secondary">{{ $course->date_range_label }}</h3>
                                    <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $course->duration_days }}-day course</p>
                                    <p class="mt-1 flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-primary">
                                        <x-icon name="map-pin" class="h-4 w-4" /> {{ $course->location->name }}
                                    </p>
                                </div>
                                <span @class([
                                    'whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide',
                                    'bg-primary/10 text-primary' => $course->remaining_places > 2,
                                    'bg-destructive/10 text-destructive' => $course->remaining_places <= 2,
                                ])>
                                    {{ $course->remaining_places }} {{ Str::plural('place', $course->remaining_places) }} left
                                </span>
                            </div>
                            <p class="mt-4 text-muted-foreground">
                                <span class="font-display text-2xl text-secondary">{{ $course->formatted_price }}</span>
                                <span class="text-sm"> — secure your place with a {{ $course->formatted_deposit }} deposit</span>
                            </p>
                        </div>
                        @if ($course->isBookable())
                            <x-ui.button :href="'/book/aff?course='.$course->id" size="lg" class="w-full bg-primary text-primary-foreground hover:bg-primary/90">
                                Book this course
                            </x-ui.button>
                        @else
                            <x-ui.button href="/contact" size="lg" variant="outline" class="w-full border-input text-secondary hover:bg-accent">
                                Join the waiting list
                            </x-ui.button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-site.section>

    <x-site.section>
        <x-site.section-heading :eyebrow="$page->info_eyebrow" :title="$page->info_title" :lead="$page->info_lead" />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($page->info_cards as $card)
                <div class="rounded-2xl border bg-card p-6">
                    <div class="text-primary"><x-icon :name="$card['icon']" /></div>
                    <h4 class="mt-2 font-display text-xl uppercase text-secondary">{{ $card['title'] }}</h4>
                    <p class="mt-1 text-muted-foreground">{{ $card['body'] }}</p>
                </div>
            @endforeach
        </div>
    </x-site.section>

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            <x-site.pay-card
                eyebrow="Reserve your spot"
                heading="Secure your place"
                body="Pick a course date, reserve your place with a deposit and start your journey to a licence."
                button="Choose a course & pay deposit"
                href="/book/aff"
            />

            {{-- AFF enquiry --}}
            <livewire:aff-enquiry-form />
        </div>
    </x-site.section>
@endsection
