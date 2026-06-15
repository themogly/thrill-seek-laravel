@props(['instructor'])
{{-- One source of truth for a small "person" chip: square brand avatar (photo or
     monogram fallback) with the name + role beneath. Reused wherever instructors
     appear compactly (the Tandem/AFF course pages). Names are always shown. --}}
<div class="flex w-28 flex-col items-center text-center">
    <x-site.avatar :name="$instructor->name" :url="$instructor->photo_url" size="h-16 w-16" />
    <p class="mt-3 font-display text-base uppercase leading-none text-secondary">{{ $instructor->name }}</p>
    @if ($instructor->role)
        <p class="mt-1.5 text-[0.7rem] font-bold uppercase leading-tight tracking-[0.15em] text-muted-foreground">{{ $instructor->role }}</p>
    @endif
</div>
