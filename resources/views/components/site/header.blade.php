@php
    $settings = app(App\Settings\GeneralSettings::class);
    $shopEnabled = $settings->shop_enabled;
    $newsEnabled = $settings->news_enabled;
    $nav = array_values(array_filter([
        ['to' => '/', 'label' => 'Home'],
        ['to' => '/tandem', 'label' => 'Tandem'],
        ['to' => '/aff', 'label' => 'AFF'],
        ['to' => '/coached', 'label' => 'Coached Skills'],
        $shopEnabled ? ['to' => '/shop', 'label' => 'Shop'] : null,
        $newsEnabled ? ['to' => '/news', 'label' => 'News'] : null,
        ['to' => '/testimonials', 'label' => 'Testimonials'],
        ['to' => '/hall-of-fame', 'label' => 'Hall of Fame'],
        ['to' => '/contact', 'label' => 'Contact'],
    ]));
    $isActive = function (string $to) {
        if ($to === '/') {
            return request()->is('/');
        }
        $slug = ltrim($to, '/');
        // Match the slug exactly or its child segments — not arbitrary prefixes,
        // so e.g. /shop never highlights for a future /shop-terms route.
        return request()->is($slug) || request()->is($slug . '/*');
    };
@endphp
{{-- The only shadow on the public site: functional, keeps the sticky header
     legible over full-bleed photography. --}}
<header x-data="{ open: false }" class="sticky top-0 z-50 w-full border-b-2 border-secondary bg-background shadow-[0_1px_8px_oklch(0.12_0.03_250_/_0.12)]">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 lg:px-8">
        <a href="/" class="flex items-center" @click="open = false">
            <img src="/images/logo.png" alt="G-Force Skydiving" class="h-10 w-auto" />
        </a>
        <nav class="hidden items-center lg:flex">
            @foreach ($nav as $item)
                <a
                    href="{{ $item['to'] }}"
                    class="border-b-2 px-3 py-2 text-sm font-bold uppercase tracking-wide transition-colors hover:text-primary {{ $isActive($item['to']) ? 'border-primary text-primary' : 'border-transparent text-secondary' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        @auth('customer')
            <a href="{{ route('account.dashboard') }}" class="mr-5 hidden text-sm font-bold uppercase tracking-widest text-secondary transition-colors hover:text-primary lg:inline-block">
                My Account
            </a>
        @endauth
        {{-- Wrapper, not `hidden` on the button: the component's base
             inline-flex and a passed `hidden` both set display, and the
             compiled CSS order — not class order — would decide. --}}
        <div class="hidden lg:block">
            <x-ui.button href="/book/tandem">
                Book Now
            </x-ui.button>
        </div>
        <button aria-label="Toggle menu" class="flex h-11 w-11 items-center justify-center text-secondary lg:hidden" @click="open = !open">
            <x-icon name="menu" x-show="!open" />
            <x-icon name="x" x-show="open" x-cloak />
        </button>
    </div>
    <div x-show="open" x-cloak class="border-t-2 border-secondary bg-background lg:hidden">
        <nav class="flex flex-col divide-y divide-border">
            @foreach ($nav as $item)
                <a
                    href="{{ $item['to'] }}"
                    @click="open = false"
                    class="px-4 py-3.5 text-sm font-bold uppercase tracking-wide {{ $isActive($item['to']) ? 'text-primary' : 'text-secondary' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
            @auth('customer')
                <a href="{{ route('account.dashboard') }}" @click="open = false" class="px-4 py-3.5 text-sm font-bold uppercase tracking-wide text-secondary">
                    My Account
                </a>
            @endauth
            <a href="/book/tandem" @click="open = false" class="bg-primary px-4 py-3.5 text-sm font-bold uppercase tracking-widest text-primary-foreground transition-colors hover:bg-primary/85">
                Book Now
            </a>
        </nav>
    </div>
</header>
