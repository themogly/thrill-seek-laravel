@props(['image', 'alt' => '', 'side' => 'right'])
{{-- Text beside a bleed photo. Holds two columns down to `md` (768px) before it
     stacks; when stacked the photo follows its text (grouped, intentional order) and
     is constrained to a landscape proportion — never an oversized full-width slab.
     `side` = which side the image sits on from `md` up; it bleeds into the gutter at
     `lg`. Reused by Tandem / AFF / Coached so the behaviour lands everywhere. --}}
@php
    $imageClasses = $side === 'left' ? 'md:order-first lg:-ml-24' : 'lg:-mr-24';
@endphp
<div class="mx-auto grid max-w-7xl items-center gap-8 px-4 md:grid-cols-2 md:gap-12 lg:px-8">
    <div data-reveal>
        {{ $slot }}
    </div>
    <div class="relative {{ $imageClasses }}" data-reveal>
        <img
            src="{{ $image }}"
            alt="{{ $alt }}"
            loading="lazy"
            width="1280"
            height="896"
            class="aspect-[16/10] max-h-[24rem] w-full object-cover md:aspect-auto md:max-h-none md:h-[24rem] lg:h-[30rem]"
        />
    </div>
</div>
