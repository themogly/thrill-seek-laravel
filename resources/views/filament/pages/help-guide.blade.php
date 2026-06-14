<x-filament-panels::page>
    <div class="mx-auto w-full max-w-3xl space-y-6">
        <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">
            Everything on the public website is managed from this panel. Here’s a plain-English
            guide — jump to a topic below, and use the “Open …” buttons to go straight there.
        </p>

        {{-- Contents / jump links --}}
        <x-filament::section>
            <x-slot name="heading">Contents</x-slot>
            <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->sections() as $section)
                    <a href="#{{ $section['id'] }}"
                       class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                        {{ $section['title'] }}
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        @foreach ($this->sections() as $section)
            <div id="{{ $section['id'] }}" class="scroll-mt-24">
                <x-filament::section :icon="$section['icon']">
                    <x-slot name="heading">{{ $section['title'] }}</x-slot>

                    <div class="space-y-3 text-sm leading-6 text-gray-600 [&_a]:font-medium [&_a]:text-primary-600 [&_a]:underline dark:text-gray-400 dark:[&_a]:text-primary-400 [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs [&_code]:text-gray-800 dark:[&_code]:bg-white/10 dark:[&_code]:text-gray-200 [&_strong]:font-semibold [&_strong]:text-gray-900 dark:[&_strong]:text-white">
                        <p>{!! $section['intro'] !!}</p>
                        <ul class="list-disc space-y-1.5 pl-5">
                            @foreach ($section['steps'] as $step)
                                <li>{!! $step !!}</li>
                            @endforeach
                        </ul>
                    </div>

                    @if ($section['cta'])
                        <div class="mt-4">
                            <x-filament::button
                                tag="a"
                                :href="$section['cta']['url']"
                                size="sm"
                                color="gray"
                                icon="heroicon-m-arrow-top-right-on-square">
                                {{ $section['cta']['label'] }}
                            </x-filament::button>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        @endforeach

        <p class="pt-2 text-center text-xs text-gray-400">
            This guide is maintained by your developer. Spotted something out of date? Let them know.
        </p>
    </div>
</x-filament-panels::page>
