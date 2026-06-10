@extends('layouts.app')

@inject('page', 'App\Settings\TandemPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)
@section('og_title', $page->og_title)
@section('og_description', $page->og_description)

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" :image="$page->imageUrl($page->hero_image)" />

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            <div>
                <x-site.section-heading :eyebrow="$page->intro_eyebrow" :title="$page->intro_title" :lead="$page->intro_lead" />
                <ul class="space-y-3">
                    @foreach ($page->bullets as $b)
                        <li class="flex items-start gap-2"><x-icon name="check" class="mt-1 h-5 w-5 flex-shrink-0 text-primary" /><span>{{ $b }}</span></li>
                    @endforeach
                </ul>

                <h3 class="mt-10 font-display text-2xl uppercase text-secondary">{{ $page->locations_heading }}</h3>
                <div class="mt-3 grid grid-cols-3 gap-3">
                    @foreach ($page->locations as $loc)
                        <div class="rounded-lg border bg-card p-4 text-center">
                            <x-icon name="map-pin" class="mx-auto h-5 w-5 text-primary" />
                            <p class="mt-1 font-semibold">{{ $loc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <img src="{{ $page->imageUrl($page->intro_image) }}" alt="Tandem skydive" class="aspect-[4/5] w-full rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
        </div>
    </x-site.section>

    @if ($product)
    <section class="bg-muted">
        <div class="mx-auto max-w-7xl px-4 py-20 lg:px-8">
            <x-site.section-heading :eyebrow="$page->pricing_eyebrow" :title="$page->pricing_title" />
            <div class="grid gap-8 lg:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border bg-card">
                    <table class="w-full text-left">
                        <thead class="bg-secondary text-secondary-foreground">
                            <tr><th class="p-4">Item</th><th class="p-4">Price</th></tr>
                        </thead>
                        <tbody>
                            <tr class="border-t">
                                <td class="p-4"><div class="font-semibold">{{ $product->name }}</div><div class="text-xs text-muted-foreground">{{ $product->price_note }}</div></td>
                                <td class="p-4 font-display text-xl text-primary">{{ $product->formatted_price }}</td>
                            </tr>
                            @foreach ($product->addOns as $addOn)
                                <tr class="border-t">
                                    <td class="p-4"><div class="font-semibold">{{ $addOn->name }}</div><div class="text-xs text-muted-foreground">{{ $addOn->note }}</div></td>
                                    <td class="p-4 font-display text-xl text-primary">{{ $addOn->formatted_price }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="overflow-hidden rounded-2xl border bg-card">
                    <div class="bg-secondary p-4 font-display uppercase text-secondary-foreground">Weight charges</div>
                    <table class="w-full text-left">
                        <tbody>
                            @foreach ($product->weight_charges ?? [] as $w)
                                <tr class="border-t"><td class="p-4 font-semibold">{{ $w['band'] }}</td><td class="p-4 font-display text-primary">{{ $w['charge'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="border-t bg-accent/50 p-4 text-sm">
                        <strong>{{ $page->charity_note_title }}</strong> {{ $page->charity_note_body }}
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            {{-- Pay card --}}
            @if ($product)
            <x-site.pay-card
                eyebrow="Book online"
                heading="Book your jump now"
                body="Pick a date, tell us about you and pay securely — booked in minutes. We use Stripe for safe, instant checkout."
                button="Choose a date & book"
                href="/book/tandem"
            >
                <ul class="mt-6 space-y-2 text-sm">
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" /> {{ $product->formatted_price }} full tandem payment</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" /> Secure Stripe checkout</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" /> Booking confirmation by email</li>
                </ul>
            </x-site.pay-card>
            @endif

            {{-- Enquiry form --}}
            <livewire:tandem-enquiry-form />
        </div>
    </x-site.section>

    {{-- GIFT VOUCHERS --}}
    <x-site.section>
        <div class="rounded-3xl bg-secondary p-10 text-center text-secondary-foreground shadow-deep lg:p-16">
            <x-icon name="sparkles" class="mx-auto h-10 w-10 text-primary" />
            <h2 class="mt-4 font-display text-4xl uppercase tracking-wide md:text-5xl">{{ $page->gift_title }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-lg opacity-90">{{ $page->gift_body }}</p>
            <x-ui.button href="/vouchers" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                {{ $page->gift_button_label }}
            </x-ui.button>
        </div>
    </x-site.section>
@endsection
