@props(['instructor'])
{{-- One source of truth for a small "person" chip: square brand avatar (photo or
     monogram fallback) with the name + role beneath. Reused wherever instructors
     appear compactly (the Tandem/AFF course pages). Names are always shown. --}}
<div class="flex w-24 flex-col items-center text-center">
    <x-site.avatar :name="$instructor->name" :url="$instructor->photo_url" size="h-14 w-14" />
    <p class="mt-2.5 font-display text-sm uppercase leading-none text-secondary">{{ $instructor->name }}</p>
    @if ($instructor->role)
        <p class="mt-1 text-[0.65rem] font-bold uppercase leading-tight tracking-[0.12em] text-muted-foreground">{{ $instructor->role }}</p>
    @endif
</div>
