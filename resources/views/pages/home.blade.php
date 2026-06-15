@extends('layouts.app')

@inject('home', 'App\Settings\HomePageSettings')
@inject('general', 'App\Settings\GeneralSettings')

@section('title', 'G-Force Skydiving — One Life. One Adventure. Live It.')
@section('description', 'UK-based skydiving school offering tandem jumps, AFF courses and advanced coaching. Book your jump today.')

@push('head')
    {{-- The hero is the LCP element — preload it. --}}
    <link rel="preload" as="image" href="{{ $home->imageUrl($home->hero_image) }}" fetchpriority="high" />
@endpush

@section('content')
    {{-- HERO: full-height photography, left-set editorial headline --}}
    <section class="relative isolate flex min-h-[32rem] items-end overflow-hidden md:min-h-[38rem] lg:min-h-[42rem]">
        <img
            src="{{ $home->imageUrl($home->hero_image) }}"
            alt="Skydivers in freefall above mountain landscape"
            class="absolute inset-0 h-full w-full object-cover"
            width="1920"
            height="1280"
            fetchpriority="high"
        />
        <div class="absolute inset-0 bg-photo-scrim"></div>
        <div class="relative z-10 mx-auto w-full max-w-7xl px-4 pb-20 pt-40 text-white lg:px-8 lg:pb-28">
            <p class="mb-5 flex items-center gap-3 text-sm font-bold uppercase tracking-[0.4em] text-sky-bright">
                <span class="inline-block h-0.5 w-12 bg-primary"></span>{{ $home->hero_eyebrow }}
            </p>
            <h1 class="font-display text-display uppercase tracking-wide">
                {{ $home->hero_title_1 }}<br />
                <span class="text-sky-bright">{{ $home->hero_title_highlight }}</span><br />
                {{ $home->hero_title_2 }}
            </h1>
            <p class="mt-8 max-w-measure border-l-4 border-primary pl-4 text-lead text-white/90">
                {{ $home->hero_subtitle }}
            </p>
            <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                <x-ui.button href="/tandem" size="lg">
                    {{ $home->hero_cta_primary_label }} <x-icon name="arrow-right" class="ml-2 h-5 w-5" />
                </x-ui.button>
                <x-ui.button href="/aff" size="lg" variant="outline">
                    {{ $home->hero_cta_secondary_label }}
                </x-ui.button>
            </div>
        </div>
        <div class="absolute bottom-6 right-6 hidden text-xs uppercase tracking-widest text-white/70 lg:block">
            Scroll ↓
        </div>
    </section>

    {{-- SERVICES: full-bleed photographic tiles, text on the image --}}
    <section class="border-b-2 border-secondary">
        <div class="mx-auto max-w-7xl px-4 pt-section-sm lg:px-8 lg:pt-section">
            <x-site.section-heading :eyebrow="$home->services_eyebrow" :title="$home->services_title" :lead="$home->services_lead" />
        </div>
        <div class="grid gap-px bg-secondary md:grid-cols-3" data-reveal>
            @foreach ($services as $s)
                <a href="{{ $s->page_path }}" class="group relative isolate flex aspect-[3/4] flex-col justify-end overflow-hidden bg-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:aspect-[4/5]">
                    <img src="{{ $s->image_url }}" alt="{{ $s->name }}" class="absolute inset-0 -z-10 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy" width="1280" height="896" />
                    <div class="absolute inset-0 -z-10 bg-photo-scrim"></div>
                    <div class="p-7 text-white">
                        <p class="font-display text-xl text-sky-bright">{{ $s->summary_price_label }}</p>
                        <h3 class="mt-1 font-display text-4xl uppercase leading-none tracking-wide lg:text-5xl">{{ $s->name }}</h3>
                        <p class="mt-3 max-w-xs text-sm text-white/85">{{ $s->summary }}</p>
                        <span class="mt-5 inline-flex items-center gap-2 border-b-2 border-primary pb-1 text-sm font-bold uppercase tracking-widest">
                            Explore <x-icon name="arrow-right" class="h-4 w-4 transition-transform motion-reduce:transition-none group-hover:translate-x-1" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ABOUT: navy band, photos bleed to the edge --}}
    <section class="band-ink overflow-hidden">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-section lg:grid-cols-2 lg:px-8 lg:py-section-lg">
            <div data-reveal>
                <p class="flex items-center gap-3 text-sm font-bold uppercase tracking-[0.25em] text-sky-bright">
                    <span class="inline-block h-0.5 w-10 bg-primary"></span>{{ $home->about_eyebrow }}
                </p>
                <h2 class="heading-rule mt-4 font-display text-h2 uppercase tracking-wide">{{ $home->about_title }}</h2>
                <p class="mt-6 max-w-measure text-lead text-white/85">
                    {{ $home->about_body }}
                </p>
                <div class="mt-10 grid grid-cols-3 divide-x divide-white/15 border-y border-white/15">
                    @foreach ($home->about_stats as $stat)
                        <div class="px-4 py-6 first:pl-0">
                            <p class="font-display text-4xl leading-none md:text-5xl">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-sky-bright">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="relative grid grid-cols-2 gap-px bg-white/15 lg:-mr-24" data-reveal>
                <img src="{{ $home->imageUrl($home->about_image_1) }}" alt="" class="aspect-[3/4] h-full w-full object-cover" loading="lazy" width="1280" height="896" />
                <img src="{{ $home->imageUrl($home->about_image_2) }}" alt="" class="aspect-[3/4] h-full w-full object-cover" loading="lazy" width="1280" height="896" />
            </div>
        </div>
    </section>

    {{-- TRUST: typographic statement band --}}
    <section class="border-b-2 border-secondary bg-secondary text-white">
        <div class="mx-auto max-w-7xl px-4 py-section-sm lg:px-8 lg:py-section">
            <p class="flex items-center gap-3 text-sm font-bold uppercase tracking-[0.25em] text-sky-bright">
                <span class="inline-block h-0.5 w-10 bg-primary"></span>{{ $home->trust_eyebrow }}
            </p>
            <h2 class="mt-3 font-display text-h2 uppercase tracking-wide">{{ $home->trust_title }}</h2>
            <x-site.trust-grid />
        </div>
    </section>

    {{-- TEAM: editorial portraits with name plates --}}
    <x-site.section>
        <x-site.section-heading :eyebrow="$home->team_eyebrow" :title="$home->team_title" :lead="$home->team_lead" />
        {{-- Horizontal scroll-snap rail: 2-up on mobile, 4-up on desktop. Each
             card uses flex-1 with a per-view min-width, so a few coaches stretch
             to fill the row (no stranded card) while five or more overflow into a
             left/right scroll instead of stacking. The rail is keyboard-focusable
             and touch-draggable; scrollbar styling keeps it discoverable. --}}
        <div
            tabindex="0"
            role="list"
            aria-label="{{ $home->team_title }}"
            class="flex snap-x snap-mandatory gap-px overflow-x-auto bg-secondary pb-3 [scrollbar-color:var(--primary)_transparent] [scrollbar-width:thin] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            data-reveal
        >
            @foreach ($instructors as $instructor)
                <div role="listitem" class="group flex min-w-[50%] flex-1 snap-start flex-col bg-background lg:min-w-[25%]">
                    <div class="relative aspect-[4/5] overflow-hidden bg-secondary">
                        @if ($instructor->photo)
                            <img src="{{ $instructor->photo_url }}" alt="{{ $instructor->name }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy" width="800" height="1000" />
                        @else
                            {{-- Intentional editorial fallback: giant monogram on navy --}}
                            <div class="band-ink flex h-full w-full items-center justify-center">
                                <span class="font-display text-[10rem] leading-none text-white/20">{{ \Illuminate\Support\Str::substr($instructor->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 border-t-4 border-primary bg-secondary/95 px-6 py-4 text-white">
                            <h3 class="font-display text-3xl uppercase leading-none">{{ $instructor->name }}</h3>
                            <p class="mt-1 text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">{{ $instructor->role }}</p>
                        </div>
                    </div>
                    <p class="flex-1 border-2 border-t-0 border-border p-6 text-muted-foreground">{{ $instructor->bio }}</p>
                </div>
            @endforeach
        </div>
    </x-site.section>

    {{-- SOCIAL + NEWS --}}
    <section class="border-y-2 border-secondary">
        <div class="mx-auto max-w-7xl px-4 py-20 lg:px-8">
            <div class="grid gap-14 @if ($latestNews->isNotEmpty()) lg:grid-cols-2 @endif">
                <div data-reveal>
                    <h2 class="flex items-center gap-3 font-display text-4xl uppercase tracking-wide text-secondary"><x-icon name="instagram" class="text-primary" /> Instagram</h2>
                    <p class="mt-2 text-muted-foreground">{{ $home->instagram_caption }}</p>
                    <div class="mt-6 grid grid-cols-3 gap-px bg-secondary">
                        @foreach ($galleryImages as $galleryImage)
                            <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" class="group relative aspect-square overflow-hidden bg-secondary">
                                <img src="{{ $galleryImage->image_url }}" alt="{{ $galleryImage->alt_text }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" />
                                <div class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/40"></div>
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">{{ $home->instagram_note }}</p>
                </div>
                @if ($latestNews->isNotEmpty())
                    <div data-reveal>
                        <h2 class="flex items-center gap-3 font-display text-4xl uppercase tracking-wide text-secondary"><x-icon name="newspaper" class="text-primary" /> Latest News</h2>
                        <p class="mt-2 text-muted-foreground">Fresh from the dropzone.</p>
                        <div class="mt-6 divide-y-2 divide-border border-2 border-border">
                            @foreach ($latestNews as $article)
                                <a href="{{ route('news.show', $article->slug) }}" class="block p-5 transition-colors hover:bg-accent/40">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">{{ $article->published_at->format('j M Y') }}</p>
                                    <p class="mt-1 font-display text-xl uppercase leading-tight text-secondary">{{ $article->title }}</p>
                                    @if (filled($article->lead))
                                        <p class="mt-1 text-sm text-muted-foreground">{{ $article->lead }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                        <x-ui.arrow-link href="/news" class="mt-4">All news</x-ui.arrow-link>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- TESTIMONIALS: editorial pull-quotes --}}
    <x-site.section>
        <x-site.section-heading :eyebrow="$home->testimonials_eyebrow" :title="$home->testimonials_title" />
        <div class="grid gap-10 md:grid-cols-3 md:gap-0 md:divide-x-2 md:divide-border" data-reveal>
            @foreach ($testimonials as $t)
                <figure class="md:px-8 md:first:pl-0 md:last:pr-0">
                    <span aria-hidden="true" class="font-display text-7xl leading-none text-primary">“</span>
                    <blockquote class="-mt-4 text-lg leading-relaxed text-foreground">{{ $t->home_quote }}</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3 border-t-2 border-primary pt-4">
                        <x-site.avatar :name="$t->name" :url="$t->avatar_url" size="h-10 w-10" />
                        <span>
                            <span class="block text-sm font-bold uppercase tracking-widest text-secondary">{{ $t->name }}</span>
                            <x-site.stars :rating="$t->rating" class="mt-1" />
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </x-site.section>

    {{-- NEWSLETTER --}}
    <section class="band-ink border-y-4 border-primary py-section lg:py-section-lg">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <h2 class="font-display text-h2 uppercase tracking-wide">{{ $home->newsletter_title }}</h2>
            @if (filled($home->newsletter_subtitle))
                <p class="mx-auto mt-4 max-w-measure text-lead text-white/85">{{ $home->newsletter_subtitle }}</p>
            @endif
            <livewire:newsletter-signup variant="banner" />
        </div>
    </section>

    {{-- CONTACT CTA: full-bleed photographic close --}}
    <section class="relative isolate overflow-hidden py-section lg:py-section-lg text-white">
        <img src="{{ $home->imageUrl($home->hero_image) }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover" loading="lazy" width="1920" height="1280" />
        <div class="absolute inset-0 -z-10 bg-photo-scrim"></div>
        <div class="mx-auto max-w-4xl px-4 text-center" data-reveal>
            <h2 class="font-display text-h1 uppercase tracking-wide">{{ $home->cta_title }}</h2>
            @if (filled($home->cta_subtitle))
                <p class="mx-auto mt-5 max-w-measure text-lead text-white/90">{{ $home->cta_subtitle }}</p>
            @endif
            <x-ui.button href="/contact" size="lg" class="mt-10">
                {{ $home->cta_button_label }}
            </x-ui.button>
        </div>
    </section>
@endsection
