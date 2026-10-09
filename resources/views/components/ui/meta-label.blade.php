@props(['as' => 'p', 'tone' => 'primary'])
{{-- The ONE small uppercase label: article dates, role labels, image labels.
     Bold, uppercase text-xs at the site's eyebrow tracking (0.25em), in a palette tone:
     `primary` on light surfaces, `sky-bright` on navy/scrim, `current` to inherit
     (e.g. white on the sky gradient). The homepage's news dates (the signed-off
     reference) carry the same classes inline. --}}
@php
    $toneClass = match ($tone) {
        'sky-bright' => 'text-sky-bright',
        'current' => null,
        default => 'text-primary',
    };
@endphp
<{{ $as }} {{ $attributes->class(['text-xs font-bold uppercase tracking-[0.25em]', $toneClass]) }}>{{ $slot }}</{{ $as }}>
