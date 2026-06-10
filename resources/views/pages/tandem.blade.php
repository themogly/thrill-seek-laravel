@extends('layouts.app')

@inject('page', 'App\Settings\TandemPageSettings')

@section('title', $page->seo_title)
@section('description', $page->seo_description)
@section('og_title', $page->og_title)
@section('og_description', $page->og_description)

@php
    $pricing = [
        ['item' => 'Tandem Skydive', 'price' => '£260', 'note' => 'Paid direct to G-Force'],
        ['item' => 'Outside Camera', 'price' => '£140', 'note' => 'Optional add-on'],
        ['item' => 'HandCam', 'price' => '£100', 'note' => 'Optional add-on'],
        ['item' => 'P6 Third Party Insurance', 'price' => '£24.73', 'note' => 'Paid on the day'],
        ['item' => 'Rebooking Fee', 'price' => '£50', 'note' => 'If you need to reschedule'],
    ];
    $weights = [
        ['Up to 15st', 'Free'],
        ['15.1 – 16st', '£20'],
        ['16.1 – 17st', '£40'],
        ['17.1 – 18st', '£60'],
        ['18st+', 'Assessment required'],
    ];
@endphp

@section('content')
    <x-site.page-hero :title="$page->hero_title" :subtitle="$page->hero_subtitle" />

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
                            @foreach ($pricing as $p)
                                <tr class="border-t">
                                    <td class="p-4"><div class="font-semibold">{{ $p['item'] }}</div><div class="text-xs text-muted-foreground">{{ $p['note'] }}</div></td>
                                    <td class="p-4 font-display text-xl text-primary">{{ $p['price'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="overflow-hidden rounded-2xl border bg-card">
                    <div class="bg-secondary p-4 font-display uppercase text-secondary-foreground">Weight charges</div>
                    <table class="w-full text-left">
                        <tbody>
                            @foreach ($weights as [$w, $p])
                                <tr class="border-t"><td class="p-4 font-semibold">{{ $w }}</td><td class="p-4 font-display text-primary">{{ $p }}</td></tr>
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

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-2">
            {{-- Pay card --}}
            <x-site.pay-card
                eyebrow="Pay online"
                heading="Book your jump now"
                body="Secure your tandem skydive with a single online payment. We use Stripe for safe, instant checkout."
                button="Pay £260 with Stripe"
                toast="Stripe checkout will be enabled once payments are connected."
            >
                <ul class="mt-6 space-y-2 text-sm">
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" /> £260 full tandem payment</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" /> Secure Stripe checkout</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" /> Booking confirmation by email</li>
                </ul>
            </x-site.pay-card>

            {{-- Enquiry form --}}
            <form x-data="enquiryForm({ delay: 600 })" @submit.prevent="submit" class="rounded-2xl border bg-card p-8 shadow-sm">
                <h3 class="font-display text-2xl uppercase text-secondary">Booking Enquiry</h3>
                <p class="mt-1 text-sm text-muted-foreground">We'll confirm availability and next steps.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2"><x-ui.label for="date">Preferred date</x-ui.label><x-ui.input id="date" name="date" type="date" required /></div>
                    <div class="space-y-2"><x-ui.label for="name">Full name</x-ui.label><x-ui.input id="name" name="name" required /></div>
                    <div class="space-y-2 sm:col-span-2"><x-ui.label for="address">Address</x-ui.label><x-ui.input id="address" name="address" required /></div>
                    <div class="space-y-2"><x-ui.label for="postcode">Postcode</x-ui.label><x-ui.input id="postcode" name="postcode" required /></div>
                    <div class="space-y-2"><x-ui.label for="dob">Date of birth</x-ui.label><x-ui.input id="dob" name="dob" type="date" required /></div>
                    <div class="space-y-2"><x-ui.label for="phone">Phone</x-ui.label><x-ui.input id="phone" name="phone" type="tel" required /></div>
                    <div class="space-y-2"><x-ui.label for="email">Email</x-ui.label><x-ui.input id="email" name="email" type="email" required /></div>
                    <div class="space-y-2"><x-ui.label for="height">Height (cm)</x-ui.label><x-ui.input id="height" name="height" type="number" required /></div>
                    <div class="space-y-2"><x-ui.label for="weight">Weight (kg)</x-ui.label><x-ui.input id="weight" name="weight" type="number" required /></div>
                    <div class="space-y-2">
                        <x-ui.label id="sex-label">Sex</x-ui.label>
                        <x-ui.select name="sex" placeholder="Select" aria-labelledby="sex-label" :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']" />
                    </div>
                </div>
                <x-ui.button type="submit" x-bind:disabled="submitting" class="mt-6 w-full bg-secondary text-secondary-foreground hover:bg-secondary/90">
                    <span x-text="submitting ? 'Sending...' : 'Send Enquiry'">Send Enquiry</span>
                </x-ui.button>
            </form>
        </div>
    </x-site.section>
@endsection
