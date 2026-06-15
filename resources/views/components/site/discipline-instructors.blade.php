@props(['instructors', 'label'])
{{-- Compact, NAMED row of the instructors who teach this discipline (tag-filtered,
     each shown once), under a quiet eyebrow label, linking to the full team page.
     The deliberate opposite of a heavy box. Renders nothing when none are tagged. --}}
@if ($instructors->isNotEmpty())
    <x-site.section>
        <div data-reveal>
            {{-- Header row: the label and the "Meet the team →" cue sit together on
                 one line (the same pattern as the home "Latest News / All news →"
                 header) — the link is grouped with the element, not floating beside
                 the avatars. A hairline rule anchors the compact row beneath. --}}
            <div class="flex items-center justify-between gap-4 border-b-2 border-border pb-3">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-primary">Your {{ $label }} instructors</p>
                <x-ui.arrow-link href="/meet-the-team">Meet the team</x-ui.arrow-link>
            </div>
            <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-5">
                @foreach ($instructors as $instructor)
                    <li>
                        <x-site.instructor-chip :instructor="$instructor" />
                    </li>
                @endforeach
            </ul>
        </div>
    </x-site.section>
@endif
