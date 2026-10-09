@props(['href' => null])
{{-- The ONE navigational arrow-link ("All news →", "Read more →"). Uppercase
     tracked, trailing arrow that slides on hover (reduced-motion respected).
     With `href` → a real <a> (own focus-visible ring + own hover). Without one →
     a <span> CUE inside a card that is itself the link, so the arrow slides on
     the parent card's hover (give the card `class="group"`). --}}
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'group/al inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-primary-strong underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2']) }}>
        {{ $slot }}
        <x-icon name="arrow-right" class="h-4 w-4 transition-transform duration-200 motion-reduce:transition-none group-hover/al:translate-x-1" />
    </a>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest']) }}>
        {{ $slot }}
        <x-icon name="arrow-right" class="h-4 w-4 transition-transform duration-200 motion-reduce:transition-none group-hover:translate-x-1" />
    </span>
@endif
