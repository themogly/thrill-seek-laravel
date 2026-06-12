@props(['variant' => 'primary', 'size' => 'default', 'href' => null, 'type' => 'button'])
{{-- The ONE button system (Round 6). Exactly three variants, palette only:
     primary — solid brand blue, the main action of any view;
     outline — 2px border in the surrounding text colour (navy on light
               surfaces, white on dark bands), for secondary actions;
     link    — quiet inline text action (no box; sizes don't apply).
     Never style a one-off button; extend here if a real need appears. --}}
@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-bold uppercase tracking-widest cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0';

    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:bg-primary/85',
        'outline' => 'border-2 border-current bg-transparent hover:bg-current/10',
        'link' => 'text-sm text-primary underline-offset-4 hover:underline',
    ];

    $sizes = [
        'default' => 'h-11 px-6 text-sm',
        'sm' => 'h-9 px-4 text-xs',
        'lg' => 'h-14 px-10 text-base',
        'icon' => 'h-11 w-11',
    ];

    $classes = trim($base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($variant === 'link' ? '' : ($sizes[$size] ?? $sizes['default'])));
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
