@extends('layouts.app')

@section('title', 'G-Force Skydiving — One Life. One Adventure. Live It.')
@section('description', 'UK-based skydiving school offering tandem jumps, AFF courses and advanced coaching. Book your jump today.')

@php
    $services = [
        ['title' => 'Tandem Skydive', 'desc' => 'Strap in with a pro and freefall from 15,000ft. Highest tandem in the UK.', 'img' => '/images/tandem.jpg', 'to' => '/tandem', 'price' => 'from £260'],
        ['title' => 'AFF Course', 'desc' => 'Become a licensed skydiver. Levels 1–8 with full kit and instruction.', 'img' => '/images/aff.jpg', 'to' => '/aff', 'price' => '£1,750'],
        ['title' => 'Coached Skills', 'desc' => '1-to-1 advanced flying coaching from world-class instructors.', 'img' => '/images/coached.jpg', 'to' => '/coached', 'price' => 'from £60'],
    ];

    $stats = [
        ['icon' => 'plane', 'value' => '15k ft', 'label' => 'Highest UK Tandem'],
        ['icon' => 'users', 'value' => '30+ yrs', 'label' => 'Combined Experience'],
        ['icon' => 'award', 'value' => 'BS / USPA', 'label' => 'Certified'],
    ];

    $instaImages = ['/images/tandem.jpg', '/images/aff.jpg', '/images/coached.jpg', '/images/hero-skydive.jpg', '/images/tandem.jpg', '/images/aff.jpg'];

    $fbPosts = [
        ['t' => 'AFF Course in Spain — June 8–12', 'd' => 'Limited spots left. Sun, blue skies and 8 jumps to A-licence.'],
        ['t' => 'Charity Tandem Day at Devon', 'd' => 'Raise money for your cause and jump from 15,000ft.'],
        ['t' => 'New G-Force Buzz tandems available', 'd' => 'Upgrade your booking with our latest kit.'],
    ];
@endphp

@section('content')
    {{-- HERO --}}
    <section class="relative isolate flex min-h-[88vh] items-center justify-center overflow-hidden">
        <img
            src="/images/hero-skydive.jpg"
            alt="Skydivers in freefall above mountain landscape"
            class="absolute inset-0 h-full w-full object-cover"
            width="1920"
            height="1280"
        />
        <div class="absolute inset-0 bg-hero-gradient"></div>
        <div class="relative z-10 mx-auto max-w-5xl px-4 text-center text-white">
            <p class="mb-4 text-sm font-bold uppercase tracking-[0.4em] text-primary">G-Force Skydiving</p>
            <h1 class="font-display text-5xl uppercase tracking-wider drop-shadow-lg md:text-8xl">
                One Life. <span class="text-primary">One Adventure.</span> Live It.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg opacity-95 md:text-xl">
                Jump with the UK's most experienced skydiving coaches. Military trained. BS &amp; USPA certified.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <x-ui.button href="/tandem" size="lg" class="bg-primary text-primary-foreground shadow-glow hover:bg-primary/90">
                    Book a Tandem <x-icon name="arrow-right" class="ml-2 h-4 w-4" />
                </x-ui.button>
                <x-ui.button href="/aff" size="lg" variant="outline" class="border-white bg-white/10 text-white backdrop-blur hover:bg-white/20">
                    Learn to Skydive
                </x-ui.button>
            </div>
        </div>
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/70 text-xs uppercase tracking-widest animate-bounce">
            Scroll
        </div>
    </section>

    {{-- SERVICES --}}
    <x-site.section>
        <x-site.section-heading eyebrow="What we do" title="Three Ways to Fly" lead="From a once-in-a-lifetime tandem to a full skydiving licence." />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($services as $s)
                <a href="{{ $s['to'] }}" class="group relative overflow-hidden rounded-2xl bg-card shadow-deep transition-transform hover:-translate-y-1">
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="{{ $s['img'] }}" alt="{{ $s['title'] }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" loading="lazy" width="1280" height="896" />
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="font-display text-2xl uppercase tracking-wide text-secondary">{{ $s['title'] }}</h3>
                            <span class="text-sm font-bold text-primary">{{ $s['price'] }}</span>
                        </div>
                        <p class="mt-2 text-muted-foreground">{{ $s['desc'] }}</p>
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
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-primary">Our Story</p>
                <h2 class="mt-3 font-display text-4xl uppercase tracking-wide md:text-5xl">Established 2017. Built on experience.</h2>
                <p class="mt-6 text-lg opacity-90">
                    G-Force Skydiving was founded by ex-military jumpers with a passion for sharing the sport safely. With
                    over 30 years of combined experience, our team holds both British Skydiving and USPA certifications,
                    and operates across the UK and Europe.
                </p>
                <div class="mt-8 grid grid-cols-3 gap-4">
                    @foreach ($stats as $stat)
                        <div>
                            <div class="text-primary"><x-icon :name="$stat['icon']" /></div>
                            <p class="mt-2 font-display text-2xl">{{ $stat['value'] }}</p>
                            <p class="text-xs uppercase tracking-wide opacity-80">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <img src="/images/tandem.jpg" alt="" class="aspect-[3/4] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
                <img src="/images/aff.jpg" alt="" class="mt-8 aspect-[3/4] rounded-2xl object-cover shadow-deep" loading="lazy" width="1280" height="896" />
            </div>
        </div>
    </section>

    {{-- TRUST STRIP --}}
    <section class="border-y bg-card">
        <div class="mx-auto max-w-7xl px-4 py-14 lg:px-8">
            <p class="text-center text-sm font-bold uppercase tracking-[0.3em] text-primary">Why jump with us</p>
            <h2 class="mt-2 text-center font-display text-3xl uppercase tracking-wide text-secondary md:text-4xl">Trusted. Certified. Experienced.</h2>
            <x-site.trust-grid />
        </div>
    </section>

    {{-- TEAM --}}
    <x-site.section>
        <x-site.section-heading eyebrow="Meet the team" title="The Coaches" lead="The people you'll fly with." />
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
                    <p class="mt-2 text-muted-foreground">Follow @gforceskydiving for jumps from the weekend.</p>
                    <div class="mt-6 grid grid-cols-3 gap-2">
                        @foreach ($instaImages as $src)
                            <a href="https://instagram.com" target="_blank" rel="noreferrer" class="group relative aspect-square overflow-hidden rounded-md bg-secondary">
                                <img src="{{ $src }}" alt="Instagram post" class="h-full w-full object-cover transition-transform group-hover:scale-110" loading="lazy" />
                                <div class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/40"></div>
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">Live Instagram feed connects via Meta Graph API — ask to enable.</p>
                </div>
                <div>
                    <h2 class="font-display text-3xl uppercase tracking-wide text-secondary flex items-center gap-3"><x-icon name="facebook" class="text-primary" /> Facebook</h2>
                    <p class="mt-2 text-muted-foreground">See our latest news and jump days.</p>
                    <div class="mt-6 space-y-3">
                        @foreach ($fbPosts as $p)
                            <a href="https://facebook.com" target="_blank" rel="noreferrer" class="block rounded-lg border bg-card p-4 transition-colors hover:border-primary">
                                <p class="font-semibold text-secondary">{{ $p['t'] }}</p>
                                <p class="text-sm text-muted-foreground">{{ $p['d'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- TESTIMONIALS --}}
    <x-site.section>
        <x-site.section-heading eyebrow="Real reviews" title="Voices from the Sky" />
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
            <h2 class="font-display text-4xl uppercase tracking-wide md:text-5xl">Stay in the loop</h2>
            <p class="mt-3 text-lg opacity-95">Course dates, jump days and member offers — direct to your inbox.</p>
            <form x-data="newsletterForm()" @submit.prevent="submit" class="mt-8 flex flex-col gap-3 sm:flex-row">
                <x-ui.input
                    type="email"
                    required
                    x-model="email"
                    placeholder="you@example.com"
                    class="h-12 flex-1 border-white/20 bg-white/10 text-white placeholder:text-white/60"
                />
                <x-ui.button type="submit" size="lg" class="h-12 bg-secondary text-secondary-foreground hover:bg-secondary/90">
                    Subscribe
                </x-ui.button>
            </form>
        </div>
    </section>

    {{-- CONTACT CTA --}}
    <x-site.section>
        <div class="rounded-3xl bg-secondary p-10 text-center text-secondary-foreground shadow-deep lg:p-16">
            <x-icon name="shield" class="mx-auto h-10 w-10 text-primary" />
            <h2 class="mt-4 font-display text-4xl uppercase tracking-wide md:text-5xl">Ready to Jump?</h2>
            <p class="mx-auto mt-3 max-w-xl text-lg opacity-90">Get in touch — we'll answer any question and help you pick the right experience.</p>
            <x-ui.button href="/contact" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                Contact Us
            </x-ui.button>
        </div>
    </x-site.section>
@endsection
