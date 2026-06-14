<form
    wire:submit="subscribe"
    x-data
    @enquiry-sent="window.toast.success($event.detail.message)"
    @enquiry-failed="window.toast.error($event.detail.message)"
    @class([
        'mt-8 flex flex-col gap-3 sm:flex-row' => $variant === 'banner',
        'mt-5 flex flex-col gap-3 sm:flex-row' => $variant === 'footer',
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
            class="h-14 flex-1 border-white/20 bg-white/10 text-white placeholder:text-white/60"
        />
    @elseif ($variant === 'footer')
        <x-ui.input
            type="email"
            required
            wire:model="email"
            placeholder="you@example.com"
            aria-label="Email address"
            autocomplete="email"
            class="flex-1 border-white/20 bg-white/10 text-white placeholder:text-white/60"
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

    {{-- The one button system: solid primary, palette only, no one-off colours.
         w-full only on the stacked card variant; compact (default) size in the footer. --}}
    <x-ui.button
        type="submit"
        variant="primary"
        size="{{ $variant === 'footer' ? 'default' : 'lg' }}"
        wire:loading.attr="disabled"
        @class(['w-full' => $variant === 'card'])
    >
        <span wire:loading.remove wire:target="subscribe">Subscribe</span>
        <span wire:loading wire:target="subscribe">Subscribing…</span>
    </x-ui.button>
</form>
