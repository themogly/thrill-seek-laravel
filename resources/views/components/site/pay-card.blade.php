@props(['eyebrow', 'heading', 'body', 'button', 'href' => null, 'toast' => null])
{{-- "Pay online" panel (tandem + aff): flat navy with a sharp primary rule.
     With an href the button is a real link (booking flows); the toast fallback
     remains for anything not yet wired up. --}}
<div class="band-ink border-t-4 border-primary p-8 lg:p-10">
    <p class="text-sm font-bold uppercase tracking-[0.25em] text-sky-bright">{{ $eyebrow }}</p>
    <h3 class="mt-3 font-display text-4xl uppercase leading-none">{{ $heading }}</h3>
    <p class="mt-4 text-white/85">{{ $body }}</p>
    {{ $slot }}
    @if ($href)
        <x-ui.button :href="$href" size="lg" class="mt-8 w-full">
            {{ $button }}
        </x-ui.button>
    @else
        <x-ui.button size="lg" class="mt-8 w-full" onclick="window.toast?.info('{{ $toast }}')">
            {{ $button }}
        </x-ui.button>
    @endif
</div>
