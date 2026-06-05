@props(['eyebrow', 'heading', 'body', 'button', 'toast'])
{{-- Fire-gradient "pay online" card (tandem + aff). Optional bullet list via slot. --}}
<div class="rounded-2xl bg-fire-gradient p-8 text-white shadow-glow">
    <p class="text-sm font-bold uppercase tracking-[0.2em] opacity-90">{{ $eyebrow }}</p>
    <h3 class="mt-2 font-display text-3xl uppercase">{{ $heading }}</h3>
    <p class="mt-3 opacity-95">{{ $body }}</p>
    {{ $slot }}
    <x-ui.button size="lg" class="mt-8 w-full bg-white text-secondary hover:bg-white/90" onclick="window.toast?.info('{{ $toast }}')">
        {{ $button }}
    </x-ui.button>
</div>
