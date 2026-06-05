@props(['title', 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'relative overflow-hidden bg-sky-gradient py-20 text-secondary-foreground lg:py-28']) }}>
    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 30%, white 1px, transparent 1px), radial-gradient(circle at 80% 70%, white 1px, transparent 1px); background-size: 60px 60px"></div>
    <div class="relative mx-auto max-w-5xl px-4 text-center lg:px-8">
        <h1 class="font-display text-5xl uppercase tracking-wider md:text-7xl">
            @foreach (explode(' ', $title) as $i => $word)<span class="{{ $i % 2 === 1 ? 'text-primary' : '' }}">{{ $word }} </span>@endforeach
        </h1>
        @if ($subtitle)
            <p class="mx-auto mt-6 max-w-2xl text-lg opacity-90">{{ $subtitle }}</p>
        @endif
    </div>
</section>
