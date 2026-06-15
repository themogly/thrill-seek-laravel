@props(['eyebrow' => null, 'title', 'lead' => null, 'light' => false])
<div {{ $attributes->merge(['class' => 'mb-14 max-w-4xl']) }} data-reveal>
    @if ($eyebrow)
        <p class="mb-3 flex items-center gap-3 text-sm font-bold uppercase tracking-[0.25em] text-primary">
            <span class="inline-block h-0.5 w-10 bg-primary"></span>{{ $eyebrow }}
        </p>
    @endif
    <h2 class="heading-rule font-display text-h2 uppercase tracking-wide {{ $light ? 'text-white' : 'text-secondary' }}">
        {{ $title }}
    </h2>
    @if ($lead)
        <p class="mt-5 max-w-measure text-lead {{ $light ? 'text-white/80' : 'text-muted-foreground' }}">{{ $lead }}</p>
    @endif
</div>
