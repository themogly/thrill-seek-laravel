@props(['id' => null])
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => 'mx-auto max-w-7xl px-4 py-section-sm lg:px-8 lg:py-section']) }}>
    {{ $slot }}
</section>
