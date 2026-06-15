@props(['type' => 'text'])
<input
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'flex h-11 w-full border-2 border-input bg-transparent px-3 py-1 text-base transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm']) }}
/>
