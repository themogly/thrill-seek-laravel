@props(['tone' => 'warning'])
{{-- Inline banner for availability / payment problems in the booking flows. --}}
<div @class([
    'rounded-xl border p-4 text-sm font-medium',
    'border-destructive/30 bg-destructive/10 text-destructive' => $tone === 'error',
    'border-primary/30 bg-primary/10 text-secondary' => $tone === 'warning',
]) role="alert">
    {{ $slot }}
</div>
