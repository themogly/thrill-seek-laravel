@props(['title', 'subtitle' => null, 'image' => null])
{{-- Subpage hero. With an image it becomes a full-bleed photographic banner
     (marketing pages); without, a deep sky gradient (transactional pages).
     Only the last word of the title is accented — in a light sky tone that
     keeps WCAG AA contrast on the dark backdrop. --}}
@php
    $words = explode(' ', $title);
    $lastIndex = count($words) - 1;
@endphp
<section {{ $attributes->merge(['class' => 'relative overflow-hidden bg-sky-gradient py-20 text-secondary-foreground lg:py-28']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover" width="1920" height="1280" />
        <div class="absolute inset-0 bg-hero-overlay"></div>
    @else
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 30%, white 1px, transparent 1px), radial-gradient(circle at 80% 70%, white 1px, transparent 1px); background-size: 60px 60px"></div>
    @endif
    <div class="relative mx-auto max-w-5xl px-4 text-center lg:px-8">
        <h1 class="font-display text-5xl uppercase tracking-wider drop-shadow-lg md:text-7xl">
            @foreach ($words as $i => $word)<span class="{{ $i === $lastIndex && $lastIndex > 0 ? 'text-hero-accent' : '' }}">{{ $word }} </span>@endforeach
        </h1>
        @if ($subtitle)
            <p class="mx-auto mt-6 max-w-2xl text-lg opacity-95">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
