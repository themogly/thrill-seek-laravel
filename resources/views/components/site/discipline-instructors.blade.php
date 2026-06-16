@props(['instructors', 'heading'])
{{-- Cross-link to the instructors who teach THIS discipline (tag-filtered, each shown
     once). Same eyebrow + heading pattern as the FAQ section directly below it
     (<x-site.section-heading>), and the SAME <x-site.instructor-card> partial as the
     Meet the Team page — chips and bio hidden here, so the cards are genuinely
     identical in style, just without the disciplines. The "Meet the Team →" arrow-link
     is grouped beneath the cards. Renders nothing when none are tagged. --}}
@if ($instructors->isNotEmpty())
    <x-site.section>
        <x-site.section-heading eyebrow="The team" :title="$heading" />
        {{-- Plain gap (not the team page's seamless gap-px-on-navy) because a single
             discipline has a variable, usually small instructor count — an empty grid
             cell must read as background, not a stray navy block. --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
            @foreach ($instructors as $instructor)
                <x-site.instructor-card :instructor="$instructor" :show-disciplines="false" :show-bio="false" heading="h3" />
            @endforeach
        </div>
        <x-ui.arrow-link href="/meet-the-team" class="mt-8">Meet the Team</x-ui.arrow-link>
    </x-site.section>
@endif
