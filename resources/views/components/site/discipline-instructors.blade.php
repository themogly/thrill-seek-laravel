@props(['instructors', 'label'])
{{-- Compact cross-link to the instructors who teach THIS discipline (tag-filtered,
     each shown once). Same card language as the Meet the Team page — a SQUARE photo
     with the navy name/role band overlaid — but WITHOUT the discipline tags (not
     needed on a single-discipline page) and scaled down for an in-page teaser. The
     "Meet the Team →" arrow-link is grouped beneath the cards, not floating beside
     them. Renders nothing when none are tagged. --}}
@if ($instructors->isNotEmpty())
    <x-site.section>
        <div data-reveal>
            <p class="flex items-center gap-3 text-sm font-bold uppercase tracking-[0.25em] text-primary">
                <span class="inline-block h-0.5 w-10 bg-primary"></span>Your {{ $label }} instructors
            </p>
            <ul class="mt-6 flex flex-wrap gap-4">
                @foreach ($instructors as $instructor)
                    <li class="group relative aspect-square w-40 overflow-hidden bg-secondary sm:w-44">
                        @if ($instructor->photo)
                            <img src="{{ $instructor->photo_url }}" alt="{{ $instructor->name }}"
                                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105 motion-reduce:transition-none"
                                 loading="lazy" width="400" height="400" />
                        @else
                            {{-- Editorial monogram fallback, matching the team page --}}
                            <div class="band-ink flex h-full w-full items-center justify-center">
                                <span class="font-display text-7xl leading-none text-white/20">{{ \Illuminate\Support\Str::substr($instructor->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 border-t-4 border-primary bg-secondary/95 px-3 py-2.5 text-white">
                            <p class="font-display text-lg uppercase leading-none">{{ $instructor->name }}</p>
                            @if ($instructor->role)
                                <p class="mt-1 text-[0.6rem] font-bold uppercase leading-tight tracking-[0.2em] text-sky-bright">{{ $instructor->role }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-ui.arrow-link href="/meet-the-team" class="mt-6">Meet the Team</x-ui.arrow-link>
        </div>
    </x-site.section>
@endif
