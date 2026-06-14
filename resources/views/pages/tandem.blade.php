@extends('layouts.app')

@inject('page', 'App\Settings\TandemPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)
@section('og_title', $page->og_title)
@section('og_description', $page->og_description)

@if ($product)
    @push('json-ld')
        <x-seo.json-ld :data="\App\Support\StructuredData::product($product, url('/tandem'), $page->seo_description)" />
    @endpush
@endif

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" :image="$page->imageUrl($page->hero_image)" />

    {{-- INTRO: copy beside a bleed photo, with Locations grouped below --}}
    <section class="overflow-hidden border-b-2 border-secondary">
        <div class="py-16 lg:py-24">
            <x-site.feature-split :image="$page->imageUrl($page->intro_image)" alt="Tandem skydive" side="right">
                <x-site.section-heading :eyebrow="$page->intro_eyebrow" :title="$page->intro_title" :lead="$page->intro_lead" class="mb-8" />
                <ul class="space-y-3">
                    @foreach ($page->bullets as $b)
                        <li class="flex items-start gap-3 border-l-2 border-primary pl-3"><x-icon name="check" class="mt-1 h-5 w-5 flex-shrink-0 text-primary" /><span>{{ $b }}</span></li>
                    @endforeach
                </ul>
            </x-site.feature-split>

            <div class="mx-auto mt-16 max-w-7xl px-4 lg:px-8" data-reveal>
                <h3 class="font-display text-3xl uppercase text-secondary">{{ $page->locations_heading }}</h3>
                <div class="mt-4 grid grid-cols-3 divide-x-2 divide-border border-y-2 border-border">
                    @foreach ($page->locations as $loc)
                        <div class="px-4 py-5 text-center">
                            <x-icon name="map-pin" class="mx-auto h-5 w-5 text-primary" />
                            <p class="mt-2 text-sm font-bold uppercase tracking-wide text-secondary">{{ $loc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if ($product)
    {{-- PRICING: flat tabular rules, no card chrome --}}
    <section class="border-b-2 border-secondary">
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-24">
            <x-site.section-heading :eyebrow="$page->pricing_eyebrow" :title="$page->pricing_title" />
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16" data-reveal>
                <div>
                    <h3 class="border-b-4 border-primary pb-2 font-display text-2xl uppercase text-secondary">The jump</h3>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-border">
                            <tr>
                                <td class="py-4 pr-4"><div class="font-semibold">{{ $product->name }}</div><div class="text-xs text-muted-foreground">{{ $product->price_note }}</div></td>
                                <td class="py-4 text-right font-display text-3xl text-primary">{{ $product->formatted_price }}</td>
                            </tr>
                            @foreach ($product->addOns as $addOn)
                                <tr>
                                    <td class="py-4 pr-4"><div class="font-semibold">{{ $addOn->name }}</div><div class="text-xs text-muted-foreground">{{ $addOn->note }}</div></td>
                                    <td class="py-4 text-right font-display text-3xl text-primary">{{ $addOn->formatted_price }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="border-b-4 border-primary pb-2 font-display text-2xl uppercase text-secondary">Weight charges</h3>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-border">
                            @foreach ($product->weight_charges ?? [] as $w)
                                <tr><td class="py-4 pr-4 font-semibold">{{ $w['band'] }}</td><td class="py-4 text-right font-display text-3xl text-primary">{{ $w['charge'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-6 border-l-4 border-primary bg-accent/40 p-4 text-sm">
                        <strong>{{ $page->charity_note_title }}</strong> {{ $page->charity_note_body }}
                    </p>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- FAQS --}}
    <x-site.faq-section :faqs="$faqs" />

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            {{-- Pay card --}}
            @php $payEnabled = app(App\Settings\GeneralSettings::class)->online_payments_enabled; @endphp
            @if ($product)
            <div data-reveal>
                <x-site.pay-card
                    eyebrow="Book online"
                    :heading="$payEnabled ? 'Book your jump now' : 'Request your jump'"
                    :body="$payEnabled
                        ? 'Pick a date, tell us about you and pay securely by card — booked in minutes.'
                        : 'Pick a date and tell us about you — we\'ll confirm your booking and arrange payment with you directly.'"
                    :button="$payEnabled ? 'Choose a date & book' : 'Choose a date & enquire'"
                    href="/book/tandem"
                >
                    <ul class="mt-6 space-y-2 text-sm text-white/90">
                        <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-sky-bright" /> {{ $product->formatted_price }} full tandem {{ $payEnabled ? 'payment' : 'price' }}</li>
                        <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-sky-bright" /> {{ $payEnabled ? 'Secure card checkout' : 'No card needed to enquire' }}</li>
                        <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-sky-bright" /> Booking confirmation by email</li>
                    </ul>
                </x-site.pay-card>
            </div>
            @endif

            {{-- Enquiry form --}}
            <livewire:tandem-enquiry-form />
        </div>
    </x-site.section>

    {{-- GIFT VOUCHERS: full-bleed navy band --}}
    <section class="band-ink border-y-4 border-primary py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-4 text-center" data-reveal>
            <h2 class="font-display text-5xl uppercase leading-[0.95] tracking-wide md:text-7xl">{{ $page->gift_title }}</h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-white/85">{{ $page->gift_body }}</p>
            <x-ui.button href="/vouchers" size="lg" class="mt-9">
                {{ $page->gift_button_label }}
            </x-ui.button>
        </div>
    </section>
@endsection
