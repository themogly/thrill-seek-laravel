@props([
    'image' => null,
    'alt' => '',
    'href' => null,
    'icon' => null,
    'monogram' => null,
    'width' => 800,
    'height' => 1000,
])
{{-- The canonical photo-tile (Round 11): a full-bleed image with a bottom
     navy scrim carrying a caption, used by Hall of Fame and Testimonials so
     the two pages read as siblings. Sharp corners, flat, structure from the
     primary top-rule on the caption. The caller sets the aspect ratio via the
     class attribute (e.g. aspect-[3/4]); the caption goes in the slot.

     - With $image: full-bleed photo + scrim (AA contrast for the white text).
     - Without: a solid navy block with a bold brand initial (intentional
       fallback, matching the coach portraits) — never a broken/empty box.
     - With $href: rendered as a focusable link with a visible focus ring;
       otherwise a plain div (display-only). Photo zoom honours reduced-motion. --}}
@php
    $classes = 'group relative block overflow-hidden bg-secondary'
        .($href ? ' focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background' : '');
    $initial = \Illuminate\Support\Str::substr($monogram ?? $alt, 0, 1);
@endphp
<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}" loading="lazy"
             class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100" />
        <div class="absolute inset-0 bg-photo-scrim transition-opacity duration-500 group-hover:opacity-90 motion-reduce:transition-none"></div>
    @else
        <div class="band-ink absolute inset-0 flex items-center justify-center">
            <span aria-hidden="true" class="font-display text-[7rem] uppercase leading-none text-white/15">{{ $initial }}</span>
        </div>
    @endif
    <div class="absolute inset-x-0 bottom-0 border-t-2 border-primary p-5 text-white">
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 text-sky-bright" />
        @endif
        {{ $slot }}
    </div>
</{{ $href ? 'a' : 'div' }}>
