@aware(['error' => null, 'for' => null])
@php($invalid = filled($error) && $for !== null && $attributes->get('id') === $for)
<textarea {{ $attributes->merge(['class' => 'flex min-h-[60px] w-full border-2 border-input bg-transparent px-3 py-2 text-base placeholder:text-muted-foreground focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm'])->merge($invalid ? ['aria-invalid' => 'true', 'aria-describedby' => $for.'-error'] : []) }}>{{ $slot }}</textarea>
