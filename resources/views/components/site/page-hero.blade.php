@props(['title', 'subtitle' => null, 'image' => null, 'compact' => false])
{{-- Subpage hero. With an image it becomes a full-bleed photographic banner
     (marketing pages); without, a COMPACT navy-gradient band — never the
     flat near-black ink band (Round 6 owner feedback). Pass `compact` for a
     shorter photographic hero that keeps a page's content (e.g. the Hall of
     Fame grid) high on the page. Only the last word of the title is accented
     — in a light sky tone that keeps WCAG AA contrast on the dark backdrop. --}}
@php
    $words = explode(' ', $title);
    $lastIndex = count($words) - 1;
    // Heroes are sized by padding (not the viewport) — a reasonable band that keeps
    // the next section visible on short/laptop screens, never a full-screen wall.
    $padding = $image ? ($compact ? 'py-12 lg:py-16' : 'py-section-sm lg:py-section') : 'py-section-sm lg:py-section';
    // Big photographic heroes get the display step; compact/gradient heroes the h1 step.
    $titleSize = $image && ! $compact ? 'text-display' : 'text-h1';
@endphp
<section {{ $attributes->merge(['class' => ($image ? 'band-ink' : 'bg-sky-gradient text-white').' '.$padding.' relative overflow-hidden border-b-4 border-primary']) }}>
    @if ($image)
        <img src="{{ $image }}" @if ($heroSrcset = \App\Support\ResponsiveImage::heroSrcset($image)) srcset="{{ $heroSrcset }}" sizes="100vw" @endif alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover" width="1920" height="1280" />
        <div class="absolute inset-0 bg-photo-scrim"></div>
    @endif
    <div class="relative mx-auto max-w-6xl px-4 lg:px-8">
        <h1 class="font-display uppercase tracking-wide {{ $titleSize }}">
            @foreach ($words as $i => $word)<span class="{{ $i === $lastIndex && $lastIndex > 0 ? 'text-sky-bright' : '' }}">{{ $word }} </span>@endforeach
        </h1>
        @if ($subtitle)
            <p class="mt-6 max-w-measure border-l-4 border-primary pl-4 text-lead text-white/90">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
