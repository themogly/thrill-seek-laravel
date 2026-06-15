@props(['instructors', 'label'])
{{-- Compact, NAMED row of the instructors who teach this discipline (tag-filtered,
     each shown once), under a quiet eyebrow label, linking to the full team page.
     The deliberate opposite of a heavy box. Renders nothing when none are tagged. --}}
@if ($instructors->isNotEmpty())
    <x-site.section>
        <div class="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between" data-reveal>
            <div class="w-full">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-primary">Your {{ $label }} instructors</p>
                <ul class="mt-6 flex flex-wrap gap-8">
                    @foreach ($instructors as $instructor)
                        <li>
                            <x-site.instructor-chip :instructor="$instructor" />
                        </li>
                    @endforeach
                </ul>
            </div>
            <x-ui.arrow-link href="/meet-the-team" class="shrink-0">Meet the team</x-ui.arrow-link>
        </div>
    </x-site.section>
@endif
