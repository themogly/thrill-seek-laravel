<form
    wire:submit="submit"
    x-data
    @enquiry-sent.window="window.toast.success($event.detail.message); $dispatch('reset')"
    @enquiry-failed.window="window.toast.error($event.detail.message)"
    class="border-2 border-secondary bg-card p-8"
>
    <h3 class="font-display text-2xl uppercase text-secondary">AFF Enquiry</h3>
    <div class="mt-6 grid gap-4">
        <div class="space-y-2"><x-ui.label for="aff-name">Full name</x-ui.label><x-ui.input id="aff-name" name="name" wire:model="name" required /></div>
        <div class="space-y-2"><x-ui.label for="aff-email">Email</x-ui.label><x-ui.input id="aff-email" name="email" type="email" wire:model="email" required /></div>
        <div class="space-y-2"><x-ui.label for="aff-phone">Phone</x-ui.label><x-ui.input id="aff-phone" name="phone" type="tel" wire:model="phone" required /></div>
        <div class="space-y-2"><x-ui.label for="aff-msg">Anything we should know?</x-ui.label><x-ui.textarea id="aff-msg" name="message" wire:model="message" rows="4" /></div>
    </div>
    <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
        <label for="aff-website">Website</label>
        <input id="aff-website" type="text" name="website" wire:model="website" tabindex="-1" autocomplete="off" />
    </div>
    <x-ui.button type="submit" wire:loading.attr="disabled" class="mt-6 w-full">
        <span wire:loading.remove wire:target="submit">Send Enquiry</span>
        <span wire:loading wire:target="submit">Sending...</span>
    </x-ui.button>
</form>
