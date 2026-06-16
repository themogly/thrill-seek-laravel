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
                        {{-- The shared arrow cue (span variant: arrow slides on the card's
                             group hover) + the distinct dark-tile border-b underline. --}}
                        <x-ui.arrow-link class="mt-5 border-b-2 border-primary pb-1">Explore</x-ui.arrow-link>
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
                {{-- Cross-link to the team, styled EXACTLY like the service-tile
                     "EXPLORE →" cue: white text, blue underline accent, arrow sliding on
                     hover. Same <x-ui.arrow-link> span variant inside a link (the service
                     cards' pattern), so it's visually identical — just labelled differently.
                     No teaser label line above it. --}}
                @if ($instructors->isNotEmpty())
                    <a href="/meet-the-team" class="group mt-10 inline-flex text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                        <x-ui.arrow-link class="border-b-2 border-primary pb-1">Meet the Team</x-ui.arrow-link>
                    </a>
                @endif
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

    {{-- NEWS + SOCIAL — real, dynamic news leads (dominant left column); an honest
         "Follow us" block sits beside it. No live-feed framing, no dead links. --}}
    <section class="border-y-2 border-secondary">
        <div class="mx-auto max-w-7xl px-4 py-section-sm lg:px-8 lg:py-section">
            <div class="grid gap-10 lg:grid-cols-3 lg:gap-12">
                @if ($latestNews->isNotEmpty())
                    {{-- Latest News — the dominant, real content (2/3 width). --}}
                    <div class="lg:col-span-2" data-reveal>
                        <h2 class="flex items-center gap-3 font-display text-h2 uppercase tracking-wide text-secondary">
                            <x-icon name="newspaper" class="h-8 w-8 shrink-0 text-primary" /> Latest News
                        </h2>
                        <p class="mt-2 text-muted-foreground">Fresh from the dropzone.</p>
                        <div class="mt-8 divide-y-2 divide-border border-2 border-border">
                            @foreach ($latestNews as $article)
                                <a href="{{ route('news.show', $article->slug) }}" class="group block p-6 transition-colors hover:bg-accent/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-primary">{{ $article->published_at->format('j M Y') }}</p>
                                    <p class="mt-2 font-display text-h3 uppercase leading-tight text-secondary transition-colors group-hover:text-primary">{{ $article->title }}</p>
                                    @if (filled($article->lead))
                                        <p class="mt-2 text-muted-foreground">{{ $article->lead }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                        <x-ui.arrow-link href="/news" class="mt-6">All news</x-ui.arrow-link>
                    </div>
                @endif

                {{-- Follow us — honest social block (1/3). Links come from CMS settings;
                     an empty URL is hidden (never a dead/generic link). --}}
                <div @class(['lg:mx-auto lg:max-w-md lg:col-span-3' => $latestNews->isEmpty()]) data-reveal>
                    <div class="band-ink h-full border-l-4 border-primary p-8">
                        <h2 class="font-display text-h3 uppercase tracking-wide">Follow us</h2>
                        <p class="mt-2 text-sm text-white/70">{{ $home->instagram_caption }}</p>
                        <div class="mt-6 flex flex-col gap-3">
                            @if (filled($general->instagram_url))
                                <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" class="group flex items-center gap-3 border border-white/20 px-4 py-3.5 transition-colors hover:border-primary hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary">
                                    <x-icon name="instagram" class="h-5 w-5 shrink-0 text-sky-bright" />
                                    <span class="text-sm font-bold uppercase tracking-widest">{{ $general->instagram_handle ?: 'Instagram' }}</span>
                                    <x-icon name="arrow-right" class="ml-auto h-4 w-4 shrink-0 text-white/50 transition-transform motion-reduce:transition-none group-hover:translate-x-1" />
                                </a>
                            @endif
                            @if (filled($general->facebook_url))
                                <a href="{{ $general->facebook_url }}" target="_blank" rel="noreferrer" class="group flex items-center gap-3 border border-white/20 px-4 py-3.5 transition-colors hover:border-primary hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary">
                                    <x-icon name="facebook" class="h-5 w-5 shrink-0 text-sky-bright" />
                                    <span class="text-sm font-bold uppercase tracking-widest">Facebook</span>
                                    <x-icon name="arrow-right" class="ml-auto h-4 w-4 shrink-0 text-white/50 transition-transform motion-reduce:transition-none group-hover:translate-x-1" />
                                </a>
                            @endif
                        </div>
                        {{-- A small CURATED set of dropzone photos (CMS Gallery) — clearly
                             owner-picked, not a live feed. Linked to Instagram when set. --}}
                        @if ($galleryImages->isNotEmpty())
                            <p class="mt-8 text-xs font-bold uppercase tracking-[0.25em] text-white/50">From the dropzone</p>
                            <div class="mt-3 grid grid-cols-3 gap-px">
                                @foreach ($galleryImages->take(6) as $galleryImage)
                                    <a @if (filled($general->instagram_url)) href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" @endif class="group relative aspect-square overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary">
                                        <img src="{{ $galleryImage->image_url }}" alt="{{ $galleryImage->alt_text }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" />
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
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
