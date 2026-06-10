@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->shop_seo_title)
@section('description', $pages->shop_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->shop_hero_title" :subtitle="$pages->shop_hero_subtitle" />
    <x-site.section>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $p)
                <div class="group rounded-2xl border bg-card p-6 transition-shadow hover:shadow-glow">
                    <div class="flex aspect-square items-center justify-center rounded-xl bg-fire-gradient text-white">
                        <x-icon name="shopping-bag" class="h-16 w-16 opacity-90" />
                    </div>
                    <div class="mt-4 flex items-start justify-between">
                        <div>
                            <h3 class="font-display text-xl uppercase text-secondary">{{ $p->name }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $p->description }}</p>
                        </div>
                        <span class="text-sm font-bold text-primary">{{ $p->price_label }}</span>
                    </div>
                    <x-ui.button class="mt-4 w-full bg-secondary text-secondary-foreground hover:bg-secondary/90" onclick="window.toast?.info('Online ordering opens soon — contact us to order.')">
                        Enquire
                    </x-ui.button>
                </div>
            @endforeach
        </div>
    </x-site.section>
@endsection
