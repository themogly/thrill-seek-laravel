@props(['variant' => 'default', 'size' => 'default', 'href' => null, 'type' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-bold uppercase tracking-widest cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0';

    $variants = [
        'default' => 'bg-primary text-primary-foreground hover:bg-secondary',
        'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        'outline' => 'border-2 border-secondary bg-transparent text-secondary hover:bg-secondary hover:text-secondary-foreground',
        'outline-light' => 'border-2 border-white bg-transparent text-white hover:bg-white hover:text-secondary',
        'secondary' => 'bg-secondary text-secondary-foreground hover:bg-primary hover:text-primary-foreground',
        'ghost' => 'hover:bg-accent hover:text-accent-foreground',
        'link' => 'text-primary underline-offset-4 hover:underline normal-case tracking-normal',
    ];

    $sizes = [
        'default' => 'h-11 px-6 text-sm',
        'sm' => 'h-9 px-4 text-xs',
        'lg' => 'h-14 px-10 text-base',
        'icon' => 'h-11 w-11',
    ];

    $classes = trim($base . ' ' . ($variants[$variant] ?? $variants['default']) . ' ' . ($sizes[$size] ?? $sizes['default']));
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
