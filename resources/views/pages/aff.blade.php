@extends('layouts.app')

@inject('page', 'App\Settings\AffPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" />

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
                heading="Pay AFF deposit"
                body="Secure your place on the next AFF course with an online deposit via Stripe."
                button="Pay Deposit with Stripe"
                toast="Stripe deposit checkout will be enabled once payments are connected."
            />

            {{-- AFF enquiry --}}
            <livewire:aff-enquiry-form />
        </div>
    </x-site.section>
@endsection
