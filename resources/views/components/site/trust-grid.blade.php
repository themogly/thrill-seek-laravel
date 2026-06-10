{{-- The shared trust band used on the home and AFF pages; items are managed
     in the admin under Settings → General → Trust badges. Round 5B: a bold
     typographic statement — oversized numerals and rules, no icon badges. --}}
@inject('general', 'App\Settings\GeneralSettings')
<div class="mt-12 grid grid-cols-1 divide-y divide-white/15 border-y border-white/15 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x" data-reveal>
    @foreach ($general->trust_items as $item)
        <div class="px-6 py-10 text-center sm:py-12 {{ $loop->index % 2 === 1 ? 'sm:border-l sm:border-white/15 lg:border-l-0' : '' }} {{ $loop->index >= 2 ? 'sm:border-t sm:border-white/15 lg:border-t-0' : '' }}">
            <p class="font-display text-5xl uppercase leading-none tracking-wide text-white md:text-6xl">{{ $item['value'] }}</p>
            <p class="mt-3 text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">{{ $item['label'] }}</p>
        </div>
    @endforeach
</div>
