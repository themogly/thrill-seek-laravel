@props(['faqs', 'eyebrow' => 'Good to know', 'title' => 'Frequently Asked Questions'])
{{-- Accessible FAQ accordion. Answers are ALWAYS in the DOM (crawlable + parity with
     the FAQPage JSON-LD) — collapsed via CSS grid-rows height, never display:none.
     Real <button> triggers (native Enter/Space + focus), aria-expanded/controls, and
     a reduced-motion-aware transition. Renders nothing when the page has no FAQs. --}}
@if ($faqs->isNotEmpty())
    {{-- FAQPage structured data — built from the SAME FAQs shown below (parity). --}}
    @push('json-ld')
        <x-seo.json-ld :data="\App\Support\StructuredData::faqPage($faqs)" />
    @endpush

    <x-site.section>
        <x-site.section-heading :eyebrow="$eyebrow" :title="$title" />
        {{-- Constrained to the reading measure and LEFT-aligned to match the (left)
             section heading — a tidy single column, chevron close to its question,
             not a full-bleed band with content pinned to the edges. --}}
        <div class="max-w-measure divide-y-2 divide-border border-y-2 border-border" data-reveal>
            @foreach ($faqs as $faq)
                <div x-data="{ open: false }">
                    <h3>
                        <button
                            type="button"
                            id="{{ $faq->panelId() }}-btn"
                            @click="open = ! open"
                            :aria-expanded="open.toString()"
                            aria-controls="{{ $faq->panelId() }}"
                            class="flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <span class="font-display text-lg uppercase tracking-wide text-secondary">{{ $faq->question }}</span>
                            <svg class="h-5 w-5 shrink-0 text-primary transition-transform duration-200 motion-reduce:transition-none" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7.5 10 12.5l5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </h3>
                    <div class="grid transition-[grid-template-rows] duration-200 ease-out motion-reduce:transition-none" :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                        <div class="overflow-hidden">
                            <div
                                id="{{ $faq->panelId() }}"
                                role="region"
                                aria-labelledby="{{ $faq->panelId() }}-btn"
                                class="pb-6 leading-relaxed text-muted-foreground [&_a]:font-bold [&_a]:text-primary [&_a]:underline [&_li]:mt-1 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-0 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5"
                            >
                                {!! $faq->answerHtml() !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-site.section>
@endif
