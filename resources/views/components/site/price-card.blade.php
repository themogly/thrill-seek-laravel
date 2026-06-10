@props(['title', 'price', 'features' => [], 'highlight' => false])
<div {{ $attributes->merge(['class' => 'border-2 p-8 ' . ($highlight ? 'border-primary' : 'border-border')]) }} data-reveal>
    <h3 class="font-display text-2xl uppercase text-secondary">{{ $title }}</h3>
    <p class="mt-3 font-display text-6xl leading-none text-primary">{{ $price }}</p>
    <ul class="mt-6 space-y-2 border-t border-border pt-6">
        @foreach ($features as $f)
            <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 shrink-0 text-primary" />{{ $f }}</li>
        @endforeach
    </ul>
</div>
