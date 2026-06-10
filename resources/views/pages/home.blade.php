@extends('layouts.app')

@inject('home', 'App\Settings\HomePageSettings')
@inject('general', 'App\Settings\GeneralSettings')

@section('title', 'G-Force Skydiving — One Life. One Adventure. Live It.')
@section('description', 'UK-based skydiving school offering tandem jumps, AFF courses and advanced coaching. Book your jump today.')

@section('content')
    {{-- HERO --}}
    <section class="relative isolate flex min-h-[88vh] items-center justify-center overflow-hidden">
        <img
            src="{{ $home->imageUrl($home->hero_image) }}"
            alt="Skydivers in freefall above mountain landscape"
            class="absolute inset-0 h-full w-full object-cover"
            width="1920"
            height="1280"
        />
        <div class="absolute inset-0 bg-hero-gradient"></div>
        <div class="relative z-10 mx-auto max-w-5xl px-4 text-center text-white">
            <p class="mb-4 text-sm font-bold uppercase tracking-[0.4em] text-hero-accent drop-shadow-md">{{ $home->hero_eyebrow }}</p>
            <h1 class="font-display text-5xl uppercase tracking-wider drop-shadow-lg md:text-8xl">
                {{ $home->hero_title_1 }} <span class="text-primary">{{ $home->hero_title_highlight }}</span> {{ $home->hero_title_2 }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg opacity-95 md:text-xl">
                {{ $home->hero_subtitle }}
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <x-ui.button href="/tandem" size="lg" class="h-14 bg-primary px-10 text-base text-primary-foreground shadow-glow transition-transform hover:scale-105 hover:bg-primary/90">
                    {{ $home->hero_cta_primary_label }} <x-icon name="arrow-right" class="ml-2 h-5 w-5" />
                </x-ui.button>
                <x-ui.button href="/aff" size="lg" variant="outline" class="h-14 border-white bg-white/10 px-8 text-base text-white backdrop-blur hover:bg-white/20">
                    {{ $home->hero_cta_secondary_label }}
                </x-ui.button>
            </div>
        </div>
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/70 text-xs uppercase tracking-widest animate-bounce">
            Scroll
        </div>
    </section>

    {{-- SERVICES --}}
    <x-site.section>
        <x-site.section-heading :eyebrow="$home->services_eyebrow" :title="$home->services_title" :lead="$home->services_lead" />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($services as $s)
                <a href="{{ $s->page_path }}" class="group relative overflow-hidden rounded-2xl bg-card shadow-deep transition-transform hover:-translate-y-1">
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="{{ $s->image_url }}" alt="{{ $s->name }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" loading="lazy" width="1280" height="896" />
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="font-display text-2xl uppercase tracking-wide text-secondary">{{ $s->name }}</h3>
                            <span class="text-sm font-bold text-primary">{{ $s->summary_price_label }}</span>
                        </div>
                        <p class="mt-2 text-muted-foreground">{{ $s->summary }}</p>
                        <span class="mt-4 inline-flex items-center text-sm font-bold uppercase text-primary">
                            Explore <x-icon name="arrow-right" class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </x-site.section>

    {{-- ABOUT --}}
    <section class="bg-secondary text-secondary-foreground">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-20 lg:grid-cols-2 lg:px-8 lg:py-28">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-primary">{{ $home->about_eyebrow }}</p>
                <h2 class="mt-3 font-display text-4xl uppercase tracking-wide md:text-5xl">{{ $home->about_title }}</h2>
                <p class="mt-6 text-lg opacity-90">
                    {{ $home->about_body }}
                </p>
                <div class="mt-8 grid grid-cols-3 gap-4">
                    @foreach ($home->about_stats as $stat)
                        <div>
                            <div class="text-primary"><x-icon :name="$stat['icon']" /></div>
                            <p class="mt-2 font-display text-2xl">{{ $stat['value'] }}</p>
                            <p class="text-xs uppercase tracking-wide opacity-80">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <img src="{{ $home->imageUrl($home->about_image_1) }}" alt="" class="aspect-[3/4] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
                <img src="{{ $home->imageUrl($home->about_image_2) }}" alt="" class="mt-8 aspect-[3/4] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
            </div>
        </div>
    </section>

    {{-- TRUST STRIP --}}
    <section class="border-y bg-card">
        <div class="mx-auto max-w-7xl px-4 py-14 lg:px-8">
            <p class="text-center text-sm font-bold uppercase tracking-[0.3em] text-primary">{{ $home->trust_eyebrow }}</p>
            <h2 class="mt-2 text-center font-display text-3xl uppercase tracking-wide text-secondary md:text-4xl">{{ $home->trust_title }}</h2>
            <x-site.trust-grid />
        </div>
    </section>

    {{-- TEAM --}}
    <x-site.section>
        <x-site.section-heading :eyebrow="$home->team_eyebrow" :title="$home->team_title" :lead="$home->team_lead" />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($instructors as $instructor)
                <div class="group rounded-2xl border bg-card p-8 text-center shadow-sm transition-all hover:shadow-glow">
                    @if ($instructor->photo)
                        <img src="{{ $instructor->photo_url }}" alt="{{ $instructor->name }}" class="mx-auto h-24 w-24 rounded-full object-cover shadow-glow" />
                    @else
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-fire-gradient text-3xl font-display text-white shadow-glow">
                            {{ \Illuminate\Support\Str::substr($instructor->name, 0, 1) }}
                        </div>
                    @endif
                    <h3 class="mt-4 font-display text-2xl uppercase text-secondary">{{ $instructor->name }}</h3>
                    <p class="text-sm font-bold uppercase tracking-wide text-primary">{{ $instructor->role }}</p>
                    <p class="mt-3 text-muted-foreground">{{ $instructor->bio }}</p>
                </div>
            @endforeach
        </div>
    </x-site.section>

    {{-- SOCIAL FEEDS --}}
    <section class="bg-muted">
        <div class="mx-auto max-w-7xl px-4 py-20 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <h2 class="font-display text-3xl uppercase tracking-wide text-secondary flex items-center gap-3"><x-icon name="instagram" class="text-primary" /> Instagram</h2>
                    <p class="mt-2 text-muted-foreground">{{ $home->instagram_caption }}</p>
                    <div class="mt-6 grid grid-cols-3 gap-2">
                        @foreach ($galleryImages as $galleryImage)
                            <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" class="group relative aspect-square overflow-hidden rounded-md bg-secondary">
                                <img src="{{ $galleryImage->image_url }}" alt="{{ $galleryImage->alt_text }}" class="h-full w-full object-cover transition-transform group-hover:scale-110" loading="lazy" />
                                <div class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/40"></div>
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">{{ $home->instagram_note }}</p>
                </div>
                <div>
                    <h2 class="font-display text-3xl uppercase tracking-wide text-secondary flex items-center gap-3"><x-icon name="facebook" class="text-primary" /> Facebook</h2>
                    <p class="mt-2 text-muted-foreground">{{ $home->facebook_caption }}</p>
                    <div class="mt-6 space-y-3">
                        @foreach ($home->facebook_posts as $p)
                            <a href="{{ $general->facebook_url }}" target="_blank" rel="noreferrer" class="block rounded-lg border bg-card p-4 transition-colors hover:border-primary">
                                <p class="font-semibold text-secondary">{{ $p['title'] }}</p>
                                <p class="text-sm text-muted-foreground">{{ $p['description'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- TESTIMONIALS --}}
    <x-site.section>
        <x-site.section-heading :eyebrow="$home->testimonials_eyebrow" :title="$home->testimonials_title" />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($testimonials as $t)
                <figure class="rounded-2xl border bg-card p-6 shadow-sm">
                    <x-icon name="sparkles" class="h-6 w-6 text-primary" />
                    <blockquote class="mt-4 text-lg">{{ $t->home_quote }}</blockquote>
                    <figcaption class="mt-4 text-sm font-bold uppercase tracking-wide text-secondary">— {{ $t->name }}</figcaption>
                </figure>
            @endforeach
        </div>
    </x-site.section>

    {{-- NEWSLETTER --}}
    <section class="bg-fire-gradient py-20 text-white">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <h2 class="font-display text-4xl uppercase tracking-wide md:text-5xl">{{ $home->newsletter_title }}</h2>
            <p class="mt-3 text-lg opacity-95">{{ $home->newsletter_subtitle }}</p>
            <livewire:newsletter-signup variant="banner" />
        </div>
    </section>

    {{-- CONTACT CTA --}}
    <x-site.section>
        <div class="rounded-3xl bg-secondary p-10 text-center text-secondary-foreground shadow-deep lg:p-16">
            <x-icon name="shield" class="mx-auto h-10 w-10 text-primary" />
            <h2 class="mt-4 font-display text-4xl uppercase tracking-wide md:text-5xl">{{ $home->cta_title }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-lg opacity-90">{{ $home->cta_subtitle }}</p>
            <x-ui.button href="/contact" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                {{ $home->cta_button_label }}
            </x-ui.button>
        </div>
    </x-site.section>
@endsection
