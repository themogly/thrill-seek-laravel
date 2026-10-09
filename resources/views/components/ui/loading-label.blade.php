@props(['target' => 'submit', 'loading' => 'Sending…'])
{{-- The ONE idle/loading label swap for a Livewire submit <x-ui.button>: the slot
     shows at rest, `loading` while `target` runs. "Sending…" (a real ellipsis) is
     defined here once, so every enquiry form says the same thing. --}}
<span wire:loading.remove wire:target="{{ $target }}">{{ $slot }}</span>
<span wire:loading wire:target="{{ $target }}">{{ $loading }}</span>
