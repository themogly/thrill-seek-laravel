@props(['variant' => 'primary', 'size' => 'default', 'href' => null, 'type' => 'button'])
{{-- The ONE button system (Round 6 · UI pass 2). Exactly three variants, palette only:
     primary — solid brand blue, the main action of any view;
     outline — 2px border in the surrounding text colour (navy on light
               surfaces, white on dark bands), for secondary actions;
     link    — quiet inline text action (no box; sizes don't apply).
     States: hover / active (press) / focus-visible ring / disabled are baked in;
     transitions respect prefers-reduced-motion. Loading is wired per-use with
     `wire:loading.attr="disabled"` + a slot label swap (Livewire forms).
     Icons: an <x-icon> in the slot is auto-sized to 16px with a gap. LEAD the icon
     for action buttons that aid scanning ("▶ Play"), TRAIL it for directional/
     destination actions ("Continue →"). An icon-only button (size="icon") MUST
     carry an `aria-label`. Never style a one-off button; extend here if needed. --}}
@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-bold uppercase tracking-widest cursor-pointer transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0';

    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:bg-primary/85 active:bg-primary/75',
        'outline' => 'border-2 border-current bg-transparent hover:bg-current/10 active:bg-current/20',
        'link' => 'text-sm text-primary underline-offset-4 hover:underline active:text-primary/80',
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
