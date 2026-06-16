@props(['items'])
{{-- The ONE feature / "what's included" list. A blue checkmark + text row, no left
     vertical line — the same treatment on every product page (Tandem / AFF / Coaching)
     so the lists can't drift apart again. Content (the items) comes from the caller's
     CMS settings; only the icon + row styling lives here. --}}
<ul {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @foreach ($items as $item)
        <li class="flex items-start gap-3">
            <x-icon name="check" class="mt-1 h-5 w-5 shrink-0 text-primary" />
            <span>{{ $item }}</span>
        </li>
    @endforeach
</ul>
