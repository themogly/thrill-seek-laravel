@props(['title', 'price', 'features' => [], 'highlight' => false])
<div class="rounded-2xl border p-8 shadow-sm {{ $highlight ? 'border-primary bg-card shadow-glow' : 'bg-card' }}">
    <h3 class="font-display text-2xl uppercase text-secondary">{{ $title }}</h3>
    <p class="mt-3 font-display text-5xl text-primary">{{ $price }}</p>
    <ul class="mt-6 space-y-2">
        @foreach ($features as $f)
            <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-primary" />{{ $f }}</li>
        @endforeach
    </ul>
</div>
