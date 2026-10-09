{{-- Top-right toast stack, driven by the window 'toast' event dispatched by
     window.toast in app.js. Palette tokens only, chosen for contrast: navy
     secondary under near-white text is 14.2:1 (the old green-600 was 3.13:1).
     The type shows as a left rule as well as the wording, never colour alone.
     A polite live region announces each toast; errors are role="alert". --}}
<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = (this._n = (this._n || 0) + 1);
            this.toasts.push({ id, type: detail.type, message: detail.message });
            setTimeout(() => { this.toasts = this.toasts.filter((t) => t.id !== id); }, 4000);
        },
    }"
    @toast.window="add($event.detail)"
    role="status"
    aria-live="polite"
    class="pointer-events-none fixed top-4 right-4 z-[100] flex w-full max-w-sm flex-col gap-2"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :role="t.type === 'error' ? 'alert' : null"
            class="pointer-events-auto rounded-md border-l-4 bg-secondary px-4 py-3 text-sm font-medium text-secondary-foreground shadow-lg"
            :class="t.type === 'error' ? 'border-destructive' : 'border-primary'"
            x-text="t.message"
        ></div>
    </template>
</div>
