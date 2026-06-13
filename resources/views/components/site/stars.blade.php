@props(['rating' => null, 'tone' => 'light'])
{{-- Star rating in palette colours only (no gold): sky-bright on dark photo
     scrims, primary on light surfaces; empty stars faded. Renders nothing
     when no rating is set. --}}
@if ($rating)
    @php
        $filled = $tone === 'dark' ? 'text-sky-bright' : 'text-primary';
        $empty = $tone === 'dark' ? 'text-white/30' : 'text-secondary/20';
    @endphp
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} role="img" aria-label="{{ $rating }} out of 5 stars">
        @for ($i = 1; $i <= 5; $i++)
            <span aria-hidden="true" class="text-sm leading-none {{ $i <= $rating ? $filled : $empty }}">&#9733;</span>
        @endfor
    </span>
@endif
