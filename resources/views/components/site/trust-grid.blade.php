{{-- The shared "Ex-Military / 30+ Years / BS-USPA / Est. 2017" trust cards used on
     the home and AFF pages. The surrounding section heading differs per page. --}}
@php
    $items = [
        ['icon' => 'shield-check', 'value' => 'Ex-Military', 'label' => 'Instructor backgrounds'],
        ['icon' => 'users', 'value' => '30+ Years', 'label' => 'Combined experience'],
        ['icon' => 'medal', 'value' => 'BS / USPA', 'label' => 'Certified instructors'],
        ['icon' => 'calendar-check', 'value' => 'Est. 2017', 'label' => 'Proven track record'],
    ];
@endphp
<div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($items as $item)
        <x-site.trust-item :icon="$item['icon']" :value="$item['value']" :label="$item['label']" />
    @endforeach
</div>
