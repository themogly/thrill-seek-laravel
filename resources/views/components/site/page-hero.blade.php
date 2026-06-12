@props(['title', 'subtitle' => null, 'image' => null])
{{-- Subpage hero. With an image it becomes a full-bleed photographic banner
     (marketing pages); without, a COMPACT navy-gradient band — never the
     flat near-black ink band (Round 6 owner feedback). Only the last word
     of the title is accented — in a light sky tone that keeps WCAG AA
     contrast on the dark backdrop. --}}
@php
    $words = explode(' ', $title);
    $lastIndex = count($words) - 1;
@endphp
<section {{ $attributes->merge(['class' => ($image ? 'band-ink py-24 lg:py-36' : 'bg-sky-gradient text-white py-16 lg:py-24') . ' relative overflow-hidden border-b-4 border-primary']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover" width="1920" height="1280" />
        <div class="absolute inset-0 bg-photo-scrim"></div>
    @endif
    <div class="relative mx-auto max-w-6xl px-4 lg:px-8">
        <h1 class="font-display uppercase leading-[0.92] tracking-wide {{ $image ? 'text-6xl md:text-8xl lg:text-9xl' : 'text-5xl md:text-7xl' }}">
            @foreach ($words as $i => $word)<span class="{{ $i === $lastIndex && $lastIndex > 0 ? 'text-sky-bright' : '' }}">{{ $word }} </span>@endforeach
        </h1>
        @if ($subtitle)
            <p class="mt-6 max-w-2xl border-l-4 border-primary pl-4 text-lg text-white/90">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
