@props(['eyebrow' => null, 'title', 'lead' => null])
<div class="mb-12 max-w-3xl">
    @if ($eyebrow)
        <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-primary">{{ $eyebrow }}</p>
    @endif
    <h2 class="font-display text-4xl uppercase tracking-wide text-secondary md:text-5xl">{{ $title }}</h2>
    @if ($lead)
        <p class="mt-4 text-lg text-muted-foreground">{{ $lead }}</p>
    @endif
</div>
