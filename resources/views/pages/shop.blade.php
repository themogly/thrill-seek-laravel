@extends('layouts.app')

@section('title', 'Shop — G-Force Skydiving Merch')
@section('description', 'G-Force Skydiving merch: t-shirts, tech tops, jumpsuits, buffs and more.')

@php
    $products = [
        ['name' => 'T-Shirt', 'price' => '£15 – £30', 'desc' => 'Classic G-Force tee in multiple colours.'],
        ['name' => 'Tech Top', 'price' => '£30', 'desc' => 'Lightweight technical top for under your suit.'],
        ['name' => 'Jumpsuit', 'price' => '£280', 'desc' => 'Made-to-measure G-Force jumpsuit.'],
        ['name' => 'Buff', 'price' => '£10', 'desc' => 'Multi-functional neck buff.'],
        ['name' => 'Gloves', 'price' => '£20', 'desc' => 'Skydiving gloves for cold-weather jumping.'],
        ['name' => 'Day Sack', 'price' => '£30', 'desc' => 'G-Force branded day sack.'],
        ['name' => 'Logbook', 'price' => '£15', 'desc' => 'Official skydiving logbook.'],
        ['name' => 'USB', 'price' => '£12', 'desc' => 'G-Force branded USB drive.'],
        ['name' => 'Water Bottle', 'price' => '£10', 'desc' => 'Insulated G-Force water bottle.'],
    ];
@endphp

@section('content')
    <x-site.page-hero title="Shop" subtitle="Kit up. Look the part. Repping G-Force on the dropzone." />
    <x-site.section>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $p)
                <div class="group rounded-2xl border bg-card p-6 transition-shadow hover:shadow-glow">
                    <div class="flex aspect-square items-center justify-center rounded-xl bg-fire-gradient text-white">
                        <x-icon name="shopping-bag" class="h-16 w-16 opacity-90" />
                    </div>
                    <div class="mt-4 flex items-start justify-between">
                        <div>
                            <h3 class="font-display text-xl uppercase text-secondary">{{ $p['name'] }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $p['desc'] }}</p>
                        </div>
                        <span class="text-sm font-bold text-primary">{{ $p['price'] }}</span>
                    </div>
                    <x-ui.button class="mt-4 w-full bg-secondary text-secondary-foreground hover:bg-secondary/90" onclick="window.toast?.info('Online ordering opens soon — contact us to order.')">
                        Enquire
                    </x-ui.button>
                </div>
            @endforeach
        </div>
    </x-site.section>
@endsection
