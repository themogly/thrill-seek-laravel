@props(['name', 'placeholder' => 'Select', 'options' => []])
@php
    // Normalize the assoc options array into a JS-friendly list of {value,label}.
    $optionList = [];
    foreach ($options as $val => $opt) {
        $optionList[] = ['value' => (string) $val, 'label' => $opt];
    }
@endphp
{{-- Accessible reproduction of the shadcn/Radix Select: a WAI-ARIA select-only
     combobox (roles + aria-activedescendant + full keyboard support + type-ahead).
     Selected value is carried by a hidden input for form submission. --}}
<div
    x-data="selectInput({ options: @js($optionList), placeholder: @js($placeholder) })"
    x-id="['select-listbox', 'select-option']"
    @click.outside="close()"
    @reset.window="reset()"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="value" />
    <button
        type="button"
        x-ref="trigger"
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open"
        :aria-controls="$id('select-listbox')"
        :aria-activedescendant="open && highlighted >= 0 ? $id('select-option', highlighted) : null"
        @click="toggle()"
        @keydown="onKeydown($event)"
        {{ $attributes->merge(['class' => 'flex h-11 w-full items-center justify-between whitespace-nowrap border-2 border-input bg-transparent px-3 py-2 text-sm ring-offset-background cursor-pointer focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1']) }}
    >
        <span x-text="value ? label : placeholder" :class="!value && 'text-muted-foreground'"></span>
        <x-icon name="chevron-down" class="h-4 w-4 opacity-50" />
    </button>
    <ul
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        :id="$id('select-listbox')"
        role="listbox"
        class="absolute z-50 mt-1 max-h-60 w-full min-w-[8rem] overflow-y-auto border-2 border-secondary bg-popover p-1 text-popover-foreground"
    >
        <template x-for="(opt, i) in options" :key="opt.value">
            <li
                :id="$id('select-option', i)"
                role="option"
                :aria-selected="value === opt.value"
                @click="select(i)"
                @mousemove="highlighted = i"
                class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none"
                :class="highlighted === i && 'bg-accent text-accent-foreground'"
            >
                <span class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center" x-show="value === opt.value">
                    <x-icon name="check" class="h-4 w-4" />
                </span>
                <span x-text="opt.label"></span>
            </li>
        </template>
    </ul>
</div>
