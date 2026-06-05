{{-- Top-right toast stack, replicating sonner (richColors). Driven by the
     window 'toast' event dispatched by window.toast in app.js. --}}
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
            class="pointer-events-auto rounded-md px-4 py-3 text-sm font-medium shadow-lg"
            :class="{ 'bg-red-600 text-white': t.type === 'error', 'bg-blue-600 text-white': t.type === 'info', 'bg-green-600 text-white': t.type !== 'error' && t.type !== 'info' }"
            x-text="t.message"
        ></div>
    </template>
</div>
