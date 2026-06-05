@props(['icon', 'value', 'label'])
<div class="group flex flex-col items-center rounded-2xl border bg-background p-6 text-center transition-all hover:-translate-y-1 hover:border-primary hover:shadow-glow">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-fire-gradient text-white shadow-glow [&_svg]:h-7 [&_svg]:w-7"><x-icon :name="$icon" /></div>
    <p class="mt-4 font-display text-2xl uppercase tracking-wide text-secondary">{{ $value }}</p>
    <p class="mt-1 text-sm font-semibold uppercase tracking-wider text-muted-foreground">{{ $label }}</p>
</div>
