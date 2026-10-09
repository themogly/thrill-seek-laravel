@props(['label'])
{{-- The ONE horizontal scroll region for a table that can be wider than the screen (prompt 024).
     A scrolling box must be reachable by keyboard (axe `scrollable-region-focusable`): it is
     focusable (tabindex 0), a named region (role + aria-label, e.g. "Payments") so a screen reader
     announces what it is, and shows the standard focus ring on keyboard focus only (focus-visible)
     — nothing changes at rest. Pass layout/border classes through; never wrap a table in a bare
     `overflow-x-auto` (guard: TablesScrollThroughTheSharedRegionTest). --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2']) }} tabindex="0" role="region" aria-label="{{ $label }}">
    {{ $slot }}
</div>
