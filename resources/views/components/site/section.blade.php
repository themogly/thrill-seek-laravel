@props(['id' => null])
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => 'mx-auto max-w-7xl px-4 py-16 lg:px-8 lg:py-24']) }}>
    {{ $slot }}
</section>
