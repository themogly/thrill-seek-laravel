@php
    $nav = [
        ['to' => '/', 'label' => 'Home'],
        ['to' => '/tandem', 'label' => 'Tandem'],
        ['to' => '/aff', 'label' => 'AFF'],
        ['to' => '/coached', 'label' => 'Coached Skills'],
        ['to' => '/shop', 'label' => 'Shop'],
        ['to' => '/testimonials', 'label' => 'Testimonials'],
        ['to' => '/hall-of-fame', 'label' => 'Hall of Fame'],
        ['to' => '/contact', 'label' => 'Contact'],
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
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/80 backdrop-blur-lg">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 lg:px-8">
        <a href="/" class="flex items-center" @click="open = false">
            <img src="/images/logo.png" alt="G-Force Skydiving" class="h-10 w-auto" />
        </a>
        <nav class="hidden items-center gap-1 lg:flex">
            @foreach ($nav as $item)
                <a
                    href="{{ $item['to'] }}"
                    class="rounded-md px-3 py-2 text-sm font-semibold uppercase tracking-wide transition-colors hover:bg-accent hover:text-secondary {{ $isActive($item['to']) ? 'text-primary' : 'text-foreground/80' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <a
            href="/book/tandem"
            class="hidden rounded-md bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-primary-foreground shadow-glow transition-transform hover:scale-105 lg:inline-flex"
        >
            Book Now
        </a>
        <button aria-label="Toggle menu" class="lg:hidden" @click="open = !open">
            <x-icon name="menu" x-show="!open" />
            <x-icon name="x" x-show="open" x-cloak />
        </button>
    </div>
    <div x-show="open" x-cloak class="border-t border-border bg-background lg:hidden">
        <nav class="flex flex-col p-2">
            @foreach ($nav as $item)
                <a
                    href="{{ $item['to'] }}"
                    @click="open = false"
                    class="rounded-md px-3 py-3 text-sm font-semibold uppercase tracking-wide {{ $isActive($item['to']) ? 'text-primary' : '' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
