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
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} aria-label="Disciplines taught">
        @foreach ($disciplines as $discipline)
            <li class="border-2 px-2.5 py-1 text-[0.7rem] font-bold uppercase leading-none tracking-widest {{ $toneClasses }}">
                {{ $discipline->name }}
            </li>
        @endforeach
    </ul>
@endif
