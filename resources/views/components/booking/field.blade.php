@props(['label', 'for', 'error' => null, 'hint' => null])
{{-- Labelled input wrapper with inline validation for the booking flows. The
     control inside (x-ui.input / textarea / date-field) picks up `error` + `for`
     with @aware and points aria-describedby at the error below. --}}
<div class="space-y-2">
    <x-ui.label :for="$for">{{ $label }}</x-ui.label>
    {{ $slot }}
    @if ($hint && ! $error)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $for }}-error" class="text-sm font-medium text-destructive" role="alert">{{ $error }}</p>
    @endif
</div>
