@props(['name', 'url' => null, 'size' => 'h-12 w-12'])
{{-- Square brand-navy avatar; falls back to the reviewer's initial when no
     photo is set (mirrors the coach-portrait monogram fallback). --}}
@if ($url)
    <img src="{{ $url }}" alt="{{ $name }}" {{ $attributes->merge(['class' => $size.' shrink-0 border-2 border-primary object-cover']) }} width="96" height="96" loading="lazy" />
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => $size.' flex shrink-0 items-center justify-center bg-secondary font-display text-xl uppercase leading-none text-white']) }}>{{ \Illuminate\Support\Str::substr($name, 0, 1) }}</span>
@endif
