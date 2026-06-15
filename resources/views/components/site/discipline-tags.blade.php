@props(['disciplines', 'tone' => 'light'])
{{-- Discipline tag chips for an instructor (Tandem / AFF / Coaching). Palette
     only: a primary-bordered chip — navy text on light cards, white text with a
     sky-bright border on dark bands. Renders nothing when untagged. --}}
@php
    $toneClasses = $tone === 'dark'
        ? 'border-sky-bright text-white'
        : 'border-primary text-secondary';
@endphp
@if ($disciplines->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-2.5']) }} aria-label="Disciplines taught">
        @foreach ($disciplines as $discipline)
            {{-- Balanced chip: even h/v padding; moderate tracking so left/right read
                 symmetric (tracking-widest pushed the glyphs left of centre). --}}
            <li class="inline-flex items-center border-2 px-3 py-1.5 text-[0.7rem] font-bold uppercase leading-none tracking-[0.1em] {{ $toneClasses }}">
                {{ $discipline->name }}
            </li>
        @endforeach
    </ul>
@endif
