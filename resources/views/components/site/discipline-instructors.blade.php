@props(['instructors', 'label'])
{{-- "Meet your <discipline> team" strip for a course page — the instructors who
     teach this discipline (tag-filtered, each shown once), linking to the full
     Meet the Team page. Renders nothing when none are tagged. --}}
@if ($instructors->isNotEmpty())
    <x-site.section>
        <div class="flex flex-col gap-6 border-2 border-border p-6 sm:flex-row sm:items-center sm:justify-between lg:p-8" data-reveal>
            <div class="flex items-center gap-5">
                {{-- Overlapping portrait thumbnails (photo or initial fallback). --}}
                <ul class="flex items-center -space-x-3" aria-hidden="true">
                    @foreach ($instructors as $instructor)
                        <li>
                            <x-site.avatar :name="$instructor->name" :url="$instructor->photo_url" size="h-14 w-14" class="ring-2 ring-background" />
                        </li>
                    @endforeach
                </ul>
                <div>
                    <p class="font-display text-2xl uppercase leading-none text-secondary">Meet your {{ $label }} team</p>
                    <p class="mt-1.5 text-muted-foreground">{{ $instructors->pluck('name')->join(', ', ' & ') }}</p>
                </div>
            </div>
            <x-ui.arrow-link href="/meet-the-team" class="shrink-0">Meet the team</x-ui.arrow-link>
        </div>
    </x-site.section>
@endif
