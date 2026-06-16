@php
    // Nav is defined here (not CMS-managed) — feature toggles hide Shop/News.
    $settings = app(App\Settings\GeneralSettings::class);
    $shopEnabled = $settings->shop_enabled;
    $newsEnabled = $settings->news_enabled;

    // Primary money/marketing pages stay one click away on the top row.
    $primaryNav = array_values(array_filter([
        ['to' => '/', 'label' => 'Home'],
        ['to' => '/tandem', 'label' => 'Tandem'],
        ['to' => '/aff', 'label' => 'AFF'],
        ['to' => '/coached', 'label' => 'Coached Skills'],
        $shopEnabled ? ['to' => '/shop', 'label' => 'Shop'] : null,
        $newsEnabled ? ['to' => '/news', 'label' => 'News'] : null,
    ]));

    // Trust content grouped under a "Why Us" dropdown — one obvious click away,
    // not buried in the footer.
    $whyUs = [
        ['to' => '/testimonials', 'label' => 'Testimonials'],
        ['to' => '/hall-of-fame', 'label' => 'Hall of Fame'],
        ['to' => '/meet-the-team', 'label' => 'Meet the Team'],
    ];

    $isActive = function (string $to) {
        if ($to === '/') {
            return request()->is('/');
        }
        $slug = ltrim($to, '/');
        // Match the slug exactly or its child segments — not arbitrary prefixes,
        // so e.g. /shop never highlights for a future /shop-terms route.
        return request()->is($slug) || request()->is($slug . '/*');
    };

    $whyUsActive = collect($whyUs)->contains(fn (array $i) => $isActive($i['to']));

    $loggedIn = auth('customer')->check();
    $accountUrl = $loggedIn ? route('account.dashboard') : route('account.login');
    $accountLabel = $loggedIn ? 'My Account' : 'Sign in';
@endphp
{{-- The only shadow on the public site: functional, keeps the sticky header
     legible over full-bleed photography. --}}
<header x-data="{ open: false, whyUsOpen: false }" class="sticky top-0 z-50 w-full border-b-2 border-secondary bg-background shadow-[0_1px_8px_oklch(0.12_0.03_250_/_0.12)]">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 lg:px-8">
        <a href="/" class="flex items-center" @click="open = false">
            <x-site.logo class="h-10 w-auto text-secondary" />
        </a>
        <nav class="hidden items-center lg:flex" aria-label="Primary">
            @foreach ($primaryNav as $item)
                <a
                    href="{{ $item['to'] }}"
                    class="border-b-2 px-3 py-2 text-sm font-bold uppercase tracking-wide transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $isActive($item['to']) ? 'border-primary text-primary' : 'border-transparent text-secondary' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach

            {{-- "Why Us" dropdown — opens on hover AND click/tap, closes on outside
                 click or Escape (which returns focus to the trigger). --}}
            <div class="relative" x-data="{ wo: false }" @mouseenter="wo = true" @mouseleave="wo = false">
                <button
                    type="button"
                    x-ref="whyUsButton"
                    @click="wo = true"
                    @keydown.escape="wo = false; $refs.whyUsButton.focus()"
                    :aria-expanded="wo.toString()"
                    aria-haspopup="true"
                    aria-controls="why-us-menu"
                    class="flex items-center gap-1 border-b-2 px-3 py-2 text-sm font-bold uppercase tracking-wide transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $whyUsActive ? 'border-primary text-primary' : 'border-transparent text-secondary' }}"
                >
                    Why Us
                    <svg class="h-3 w-3 transition-transform duration-200" :class="{ 'rotate-180': wo }" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M2.5 4.5 6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <div
                    id="why-us-menu"
                    x-show="wo"
                    x-cloak
                    x-transition.opacity.duration.150ms
                    @click.outside="wo = false"
                    @keydown.escape="wo = false; $refs.whyUsButton.focus()"
                    role="menu"
                    aria-label="Why Us"
                    class="absolute left-0 top-full mt-px w-52 border-2 border-secondary bg-background shadow-[0_4px_12px_oklch(0.12_0.03_250_/_0.15)]"
                >
                    @foreach ($whyUs as $item)
                        <a
                            href="{{ $item['to'] }}"
                            role="menuitem"
                            class="block px-4 py-3 text-sm font-bold uppercase tracking-wide transition-colors hover:bg-muted hover:text-primary focus-visible:bg-muted focus-visible:text-primary focus-visible:outline-none {{ $isActive($item['to']) ? 'text-primary' : 'text-secondary' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <a
                href="/contact"
                class="border-b-2 px-3 py-2 text-sm font-bold uppercase tracking-wide transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $isActive('/contact') ? 'border-primary text-primary' : 'border-transparent text-secondary' }}"
            >
                Contact
            </a>
        </nav>

        {{-- Action links: account + Book Now, grouped and distinct from the nav. --}}
        <div class="hidden items-center gap-5 lg:flex">
            <a href="{{ $accountUrl }}" class="text-sm font-bold uppercase tracking-widest text-secondary transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                {{ $accountLabel }}
            </a>
            <x-ui.button href="/book/tandem">
                Book Now
            </x-ui.button>
        </div>

        <button aria-label="Toggle menu" :aria-expanded="open.toString()" class="flex h-11 w-11 items-center justify-center text-secondary lg:hidden" @click="open = !open">
            <x-icon name="menu" x-show="!open" />
            <x-icon name="x" x-show="open" x-cloak />
        </button>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open" x-cloak class="border-t-2 border-secondary bg-background lg:hidden">
        <nav class="flex flex-col divide-y divide-border" aria-label="Mobile">
            @foreach ($primaryNav as $item)
                <a
                    href="{{ $item['to'] }}"
                    @click="open = false"
                    class="px-4 py-3.5 text-sm font-bold uppercase tracking-wide {{ $isActive($item['to']) ? 'text-primary' : 'text-secondary' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach

            {{-- "Why Us" as an expandable group in the mobile menu. --}}
            <div>
                <button
                    type="button"
                    @click="whyUsOpen = !whyUsOpen"
                    :aria-expanded="whyUsOpen.toString()"
                    aria-controls="why-us-mobile"
                    class="flex w-full items-center justify-between px-4 py-3.5 text-sm font-bold uppercase tracking-wide {{ $whyUsActive ? 'text-primary' : 'text-secondary' }}"
                >
                    Why Us
                    <svg class="h-3 w-3 transition-transform duration-200" :class="{ 'rotate-180': whyUsOpen }" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M2.5 4.5 6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <div id="why-us-mobile" x-show="whyUsOpen" x-collapse.duration.200ms class="bg-muted/40">
                    @foreach ($whyUs as $item)
                        <a
                            href="{{ $item['to'] }}"
                            @click="open = false"
                            class="block px-8 py-3 text-sm font-bold uppercase tracking-wide {{ $isActive($item['to']) ? 'text-primary' : 'text-secondary' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <a
                href="/contact"
                @click="open = false"
                class="px-4 py-3.5 text-sm font-bold uppercase tracking-wide {{ $isActive('/contact') ? 'text-primary' : 'text-secondary' }}"
            >
                Contact
            </a>

            <a href="{{ $accountUrl }}" @click="open = false" class="px-4 py-3.5 text-sm font-bold uppercase tracking-widest text-secondary">
                {{ $accountLabel }}
            </a>
            <div class="px-4 pt-2">
                <x-ui.button href="/book/tandem" class="w-full" x-on:click="open = false">Book Now</x-ui.button>
            </div>
        </nav>
    </div>
</header>
