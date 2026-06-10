<form
    wire:submit="submit"
    x-data
    @enquiry-sent.window="window.toast.success($event.detail.message); $dispatch('reset')"
    @enquiry-failed.window="window.toast.error($event.detail.message)"
    class="rounded-2xl border bg-card p-8 shadow-sm"
>
    <h2 class="font-display text-2xl uppercase text-secondary">{{ $heading }}</h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="space-y-2"><x-ui.label for="c-name">Name</x-ui.label><x-ui.input id="c-name" name="name" wire:model="name" required maxlength="100" /></div>
        <div class="space-y-2"><x-ui.label for="c-email">Email</x-ui.label><x-ui.input id="c-email" name="email" type="email" wire:model="email" required maxlength="255" /></div>
        <div class="space-y-2 sm:col-span-2"><x-ui.label for="c-phone">Phone (optional)</x-ui.label><x-ui.input id="c-phone" name="phone" type="tel" wire:model="phone" maxlength="30" /></div>
        <div class="space-y-2 sm:col-span-2"><x-ui.label for="c-msg">Message</x-ui.label><x-ui.textarea id="c-msg" name="message" wire:model="message" rows="6" required maxlength="2000" /></div>
    </div>
    <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
        <label for="c-website">Website</label>
        <input id="c-website" type="text" name="website" wire:model="website" tabindex="-1" autocomplete="off" />
    </div>
    <x-ui.button type="submit" wire:loading.attr="disabled" class="mt-6 bg-primary text-primary-foreground hover:bg-primary/90">
        <x-icon name="send" class="mr-2 h-4 w-4" />
        <span wire:loading.remove wire:target="submit">Send Message</span>
        <span wire:loading wire:target="submit">Sending...</span>
    </x-ui.button>
</form>
