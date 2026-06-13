@extends('layouts.app')

@inject('page', 'App\Settings\AffPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" :image="$page->imageUrl($page->hero_image)" />

    {{-- INTRO: image bleeds off the left edge --}}
    <section class="overflow-hidden border-b-2 border-secondary">
        <div class="mx-auto grid max-w-7xl items-stretch gap-12 px-4 py-16 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div class="relative order-last lg:order-first lg:-ml-24" data-reveal>
                <img src="{{ $page->imageUrl($page->intro_image) }}" alt="AFF training" class="h-full min-h-[24rem] w-full object-cover" loading="lazy" width="1280" height="896" />
            </div>
            <div data-reveal>
                <x-site.section-heading :eyebrow="$page->intro_eyebrow" :title="$page->intro_title" :lead="$page->intro_lead" class="mb-8" />
                <ul class="space-y-3">
                    @foreach ($page->bullets as $b)
                        <li class="flex items-start gap-3 border-l-2 border-primary pl-3"><x-icon name="check" class="mt-1 h-5 w-5 flex-shrink-0 text-primary" /><span>{{ $b }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- TRUST: dark typographic band --}}
    <section class="border-b-2 border-secondary bg-secondary text-white">
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-20">
            <div class="max-w-3xl">
                <p class="flex items-center gap-3 text-sm font-bold uppercase tracking-[0.3em] text-sky-bright">
                    <span class="inline-block h-0.5 w-10 bg-primary"></span>{{ $page->trust_eyebrow }}
                </p>
                <h2 class="mt-3 font-display text-4xl uppercase leading-none tracking-wide md:text-6xl">{{ $page->trust_title }}</h2>
                <p class="mt-4 text-white/80">
                    {{ $page->trust_body }}
                </p>
            </div>
            <x-site.trust-grid />
        </div>
    </section>

    {{-- PRICING --}}
    <section class="border-b-2 border-secondary">
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-24">
            <x-site.section-heading :eyebrow="$page->pricing_eyebrow" :title="$page->pricing_title" />
            <div class="grid gap-px bg-secondary md:grid-cols-2" data-reveal>
                @foreach ($products as $p)
                    <div class="bg-background">
                        <x-site.price-card :title="$p->name" :price="$p->formatted_price" :features="$p->features ?? []" :highlight="$p->highlight" class="h-full border-0" />
                    </div>
                @endforeach
            </div>
            @foreach ($products->filter(fn ($p) => filled($p->repeat_pricing)) as $p)
                <div class="mt-10 border-l-4 border-primary pl-6" data-reveal>
                    <h3 class="font-display text-2xl uppercase text-secondary">{{ $page->repeat_pricing_heading }}</h3>
                    <div class="mt-4 grid gap-x-10 gap-y-2 sm:grid-cols-2">
                        @foreach ($p->repeat_pricing as $row)
                            <div class="flex items-baseline justify-between gap-4 border-b border-border py-2"><span class="font-semibold">{{ $row['label'] }}</span> <span class="font-display text-xl text-primary">{{ $row['value'] }}</span></div>
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
            <div class="border-2 border-secondary p-10 text-center">
                <p class="text-lg text-muted-foreground">{{ $page->courses_empty_text }}</p>
                <x-ui.button href="/contact" class="mt-6">Get in touch</x-ui.button>
            </div>
        @else
            <div class="grid gap-px bg-secondary md:grid-cols-2" data-reveal>
                @foreach ($courseDates as $course)
                    <div class="flex flex-col justify-between gap-6 border-t-4 bg-background p-6 sm:p-8 {{ $course->remaining_places <= 2 ? 'border-destructive' : 'border-primary' }}">
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-display text-4xl uppercase leading-none text-secondary">{{ $course->date_range_label }}</h3>
                                    <p class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground">{{ $course->duration_days }}-day course</p>
                                    <p class="mt-1 flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-primary">
                                        <x-icon name="map-pin" class="h-4 w-4" /> {{ $course->location->name }}
                                    </p>
                                </div>
                                <span @class([
                                    'whitespace-nowrap border-2 px-3 py-1 text-xs font-bold uppercase tracking-wide',
                                    'border-primary text-primary' => $course->remaining_places > 2,
                                    'border-destructive text-destructive' => $course->remaining_places <= 2,
                                ])>
                                    {{ $course->remaining_places }} {{ Str::plural('place', $course->remaining_places) }} left
                                </span>
                            </div>
                            <p class="mt-5 border-t border-border pt-4 text-muted-foreground">
                                <span class="font-display text-3xl text-secondary">{{ $course->formatted_price }}</span>
                                <span class="text-sm"> — secure your place with a {{ $course->formatted_deposit }} deposit</span>
                            </p>
                        </div>
                        @if ($course->isBookable())
                            <x-ui.button :href="'/book/aff?course='.$course->id" size="lg" class="w-full">
                                Book this course
                            </x-ui.button>
                        @else
                            <x-ui.button href="/contact" size="lg" variant="outline" class="w-full">
                                Join the waiting list
                            </x-ui.button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-site.section>

    {{-- INFO: ruled columns, no cards --}}
    <section class="border-y-2 border-secondary">
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-24">
            <x-site.section-heading :eyebrow="$page->info_eyebrow" :title="$page->info_title" :lead="$page->info_lead" />
            <div class="grid gap-10 md:grid-cols-3 md:gap-0 md:divide-x-2 md:divide-border" data-reveal>
                @foreach ($page->info_cards as $card)
                    <div class="md:px-8 md:first:pl-0 md:last:pr-0">
                        <div class="text-primary"><x-icon :name="$card['icon']" /></div>
                        <h4 class="mt-3 font-display text-2xl uppercase text-secondary">{{ $card['title'] }}</h4>
                        <p class="mt-2 text-muted-foreground">{{ $card['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            @php $payEnabled = app(App\Settings\GeneralSettings::class)->online_payments_enabled; @endphp
            <div data-reveal>
                <x-site.pay-card
                    eyebrow="Reserve your spot"
                    heading="Secure your place"
                    :body="$payEnabled
                        ? 'Pick a course date, reserve your place with a deposit and start your journey to a licence.'
                        : 'Pick a course date and send us your details — we\'ll confirm your place and arrange the deposit with you directly.'"
                    :button="$payEnabled ? 'Choose a course & pay deposit' : 'Choose a course & enquire'"
                    href="/book/aff"
                />
            </div>

            {{-- AFF enquiry --}}
            <livewire:aff-enquiry-form />
        </div>
    </x-site.section>
@endsection
