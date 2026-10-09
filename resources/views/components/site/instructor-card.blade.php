@props([
    'instructor',
    'showDisciplines' => true,
    'showBio' => true,
    'heading' => 'h2',
])
{{-- One source of truth for an instructor card: a SQUARE (1:1) photo with the navy
     name/role band overlaid, and an optional content area beneath (discipline chips
     and/or bio). The Meet the Team page shows everything; the discipline-page teaser
     reuses the SAME card with the chips and bio hidden (image + band only), so the
     two are genuinely identical in style. `heading` sets the name's tag (h2 under the
     team-page hero, h3 under a section-heading on the discipline pages). --}}
<article class="group flex flex-col bg-background">
    <div class="relative aspect-square overflow-hidden bg-secondary">
        @if ($instructor->photo)
            <img src="{{ $instructor->photo_url }}" alt="{{ $instructor->name }}"
                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105 motion-reduce:transition-none"
                 loading="lazy" width="800" height="800" />
        @else
            {{-- Intentional editorial fallback: giant monogram on navy --}}
            <div class="band-ink flex h-full w-full items-center justify-center">
                <span class="font-display text-[10rem] leading-none text-white/20">{{ \Illuminate\Support\Str::substr($instructor->name, 0, 1) }}</span>
            </div>
        @endif
        <div class="absolute inset-x-0 bottom-0 border-t-4 border-primary bg-secondary/95 px-6 py-4 text-white">
            <{{ $heading }} class="font-display text-3xl uppercase leading-none">{{ $instructor->name }}</{{ $heading }}>
            @if ($instructor->role)
                <x-ui.meta-label tone="sky-bright" class="mt-1">{{ $instructor->role }}</x-ui.meta-label>
            @endif
        </div>
    </div>
    @if (($showDisciplines && $instructor->disciplines->isNotEmpty()) || ($showBio && filled($instructor->bio)))
        <div class="flex flex-1 flex-col border-2 border-t-0 border-border p-6 lg:p-7">
            {{-- Discipline chips stand on their own (no "Teaches" label — it read
                 awkwardly as "teaches coaching"); deliberate spacing between the role
                 band and the bio. --}}
            @if ($showDisciplines && $instructor->disciplines->isNotEmpty())
                <div class="mb-6 mt-1">
                    <x-site.discipline-tags :disciplines="$instructor->disciplines" />
                </div>
            @endif
            @if ($showBio && filled($instructor->bio))
                <p class="text-muted-foreground">{{ $instructor->bio }}</p>
            @endif
        </div>
    @endif
</article>
