@props([
    'model',
    'name' => null,
    'id' => null,
    'min' => null,
    'max' => null,
    'required' => false,
    'autocomplete' => null,
    'label' => 'Choose date',
])
@php($name = $name ?? $model)
@php($id = $id ?? $name)
@aware(['error' => null, 'for' => null])
@php($invalid = filled($error) && $for === $id)
{{-- Branded date input. The native <input type="date"> is the single source of
     truth (the submitted YYYY-MM-DD value + the accessible mobile control). On a
     fine-pointer desktop, an Alpine calendar overlays it (clicking anywhere on the
     field opens a styled popover; month + year jump for DOB; full keyboard support)
     and writes the chosen date back to the native input — the value never changes.
     See app.js `dateField` + DECISIONS.md. --}}
<div x-data="dateField()" x-id="['dp']" class="relative" @keydown.escape.stop="open && close()">
    {{-- Value carrier. Shown as a normal field on mobile; sr-only on desktop. --}}
    <input
        x-ref="native"
        type="date"
        id="{{ $id }}"
        name="{{ $name }}"
        wire:model="{{ $model }}"
        @if ($min) min="{{ $min }}" @endif
        @if ($max) max="{{ $max }}" @endif
        @if ($required) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $for }}-error" @endif
        x-show="!enhanced"
        :tabindex="enhanced ? -1 : null"
        :aria-hidden="enhanced ? 'true' : null"
        {{ $attributes->merge(['class' => 'flex h-11 w-full border-2 border-input bg-transparent px-3 py-1 text-base transition-colors placeholder:text-muted-foreground focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm']) }}
    />

    {{-- Desktop styled trigger — clicking anywhere opens the calendar. --}}
    <button
        x-ref="trigger"
        x-show="enhanced"
        x-cloak
        type="button"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="dialog"
        :aria-controls="$id('dp')"
        aria-label="{{ $label }}"
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $for }}-error" @endif
        class="flex h-11 w-full items-center justify-between gap-2 border-2 border-input bg-transparent px-3 text-left text-sm transition-colors hover:border-primary focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    >
        <span x-text="display || 'Select a date'" :class="display || 'text-muted-foreground'"></span>
        <x-icon name="calendar" class="h-4 w-4 shrink-0 text-muted-foreground" />
    </button>

    {{-- Calendar popover (desktop). --}}
    <div
        x-show="open && enhanced"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        @click.outside="open = false"
        x-ref="cal"
        :id="$id('dp')"
        role="dialog"
        aria-modal="false"
        aria-label="{{ $label }}"
        class="absolute z-50 mt-1 w-[20rem] border-2 border-secondary bg-popover p-4 text-popover-foreground shadow-deep"
    >
        {{-- Header: prev / month + year selects / next --}}
        <div class="flex items-center gap-2">
            <button type="button" @click="prevMonth()" aria-label="Previous month" class="flex h-9 w-9 shrink-0 items-center justify-center border-2 border-input transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </button>
            <div class="flex flex-1 gap-2">
                <select x-model.number="viewMonth" aria-label="Month" class="h-9 w-full border-2 border-input bg-transparent px-2 text-sm focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <template x-for="(m, i) in months" :key="i"><option :value="i" x-text="m"></option></template>
                </select>
                <select x-model.number="viewYear" aria-label="Year" class="h-9 w-[5.5rem] shrink-0 border-2 border-input bg-transparent px-2 text-sm focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
                </select>
            </div>
            <button type="button" @click="nextMonth()" aria-label="Next month" class="flex h-9 w-9 shrink-0 items-center justify-center border-2 border-input transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </button>
        </div>

        {{-- Weekday labels (Monday-first) --}}
        <div class="mt-3 grid grid-cols-7 text-center text-xs font-bold uppercase tracking-widest text-muted-foreground">
            @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $d)<span class="py-1">{{ $d }}</span>@endforeach
        </div>

        {{-- Day grid --}}
        <div class="mt-1 grid grid-cols-7 gap-px" role="grid" @keydown="onGridKey($event)">
            <template x-for="(d, i) in grid" :key="i">
                <div>
                    <button
                        x-show="d !== null"
                        type="button"
                        @click="pick(d)"
                        :disabled="disabled(d)"
                        :tabindex="d === focusDay ? 0 : -1"
                        :data-focus="(d === focusDay).toString()"
                        :aria-selected="isSelected(d).toString()"
                        :aria-current="isToday(d) ? 'date' : null"
                        x-text="d"
                        class="flex h-9 w-full items-center justify-center text-sm tabular-nums transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring disabled:cursor-not-allowed disabled:text-muted-foreground/30"
                        :class="{
                            'bg-primary-strong text-primary-foreground font-bold': isSelected(d),
                            'hover:bg-accent': !isSelected(d) && !disabled(d),
                            'ring-1 ring-inset ring-primary': isToday(d) && !isSelected(d),
                        }"
                    ></button>
                </div>
            </template>
        </div>
    </div>
</div>
