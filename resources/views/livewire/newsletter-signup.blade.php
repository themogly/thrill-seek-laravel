<form
    wire:submit="subscribe"
    x-data
    @enquiry-sent.window="window.toast.success($event.detail.message)"
    @enquiry-failed.window="window.toast.error($event.detail.message)"
    @class([
        'mt-8 flex flex-col gap-3 sm:flex-row' => $variant === 'banner',
        'mt-4 flex flex-col gap-3' => $variant === 'card',
    ])
>
    @if ($variant === 'banner')
        <x-ui.input
            type="email"
            required
            wire:model="email"
            placeholder="you@example.com"
            aria-label="Email address"
            autocomplete="email"
            class="h-12 flex-1 border-white/20 bg-white/10 text-white placeholder:text-white/60"
        />
    @else
        <x-ui.input
            type="email"
            required
            wire:model="email"
            placeholder="you@example.com"
            aria-label="Email address"
            autocomplete="email"
        />
    @endif

    <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
        <label for="nl-website-{{ $variant }}">Website</label>
        <input id="nl-website-{{ $variant }}" type="text" wire:model="website" tabindex="-1" autocomplete="off" />
    </div>

    <x-ui.button
        type="submit"
        size="lg"
        wire:loading.attr="disabled"
        @class([
            'h-12 bg-secondary text-secondary-foreground hover:bg-secondary/90' => $variant === 'banner',
            'w-full bg-secondary text-secondary-foreground hover:bg-secondary/90' => $variant === 'card',
        ])
    >
        <span wire:loading.remove wire:target="subscribe">Subscribe</span>
        <span wire:loading wire:target="subscribe">Subscribing…</span>
    </x-ui.button>
</form>
