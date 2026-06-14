@props(['data'])
{{-- Reusable JSON-LD emitter. Pages push structured data into the head:
     @push('json-ld') <x-seo.json-ld :data="[...]" /> @endpush
     Data must be real (from models/CMS) — never invented. --}}
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
