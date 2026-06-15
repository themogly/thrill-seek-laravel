<form
    wire:submit="submit"
    x-data
    @enquiry-sent="window.toast.success($event.detail.message); $dispatch('reset')"
    @enquiry-failed="window.toast.error($event.detail.message)"
    class="border-2 border-secondary bg-card p-8"
>
    <h3 class="font-display text-2xl uppercase text-secondary">Booking Enquiry</h3>
    <p class="mt-1 text-sm text-muted-foreground">We'll confirm availability and next steps.</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="space-y-2"><x-ui.label for="date">Preferred date</x-ui.label><x-ui.date-field id="date" model="date" label="Preferred date" :min="now()->toDateString()" required /></div>
        <div class="space-y-2"><x-ui.label for="name">Full name</x-ui.label><x-ui.input id="name" name="name" wire:model="name" required /></div>
        <div class="space-y-2 sm:col-span-2"><x-ui.label for="address">Address</x-ui.label><x-ui.input id="address" name="address" wire:model="address" required /></div>
        <div class="space-y-2"><x-ui.label for="postcode">Postcode</x-ui.label><x-ui.input id="postcode" name="postcode" wire:model="postcode" required /></div>
        <div class="space-y-2"><x-ui.label for="dob">Date of birth</x-ui.label><x-ui.date-field id="dob" model="dob" label="Date of birth" :max="now()->subDay()->toDateString()" autocomplete="bday" required /></div>
        <div class="space-y-2"><x-ui.label for="phone">Phone</x-ui.label><x-ui.input id="phone" name="phone" type="tel" wire:model="phone" required /></div>
        <div class="space-y-2"><x-ui.label for="email">Email</x-ui.label><x-ui.input id="email" name="email" type="email" wire:model="email" required /></div>
        <div class="space-y-2"><x-ui.label for="height">Height (cm)</x-ui.label><x-ui.input id="height" name="height" type="number" wire:model="height" required /></div>
        <div class="space-y-2"><x-ui.label for="weight">Weight (kg)</x-ui.label><x-ui.input id="weight" name="weight" type="number" wire:model="weight" required /></div>
        <div class="space-y-2">
            <x-ui.label id="sex-label">Sex</x-ui.label>
            <x-ui.select
                name="sex"
                placeholder="Select"
                aria-labelledby="sex-label"
                :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']"
                x-init="$watch('value', v => $wire.set('sex', v, false))"
            />
        </div>
    </div>
    <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
        <label for="t-website">Website</label>
        <input id="t-website" type="text" name="website" wire:model="website" tabindex="-1" autocomplete="off" />
    </div>
    <x-ui.button type="submit" wire:loading.attr="disabled" class="mt-6 w-full">
        <span wire:loading.remove wire:target="submit">Send Enquiry</span>
        <span wire:loading wire:target="submit">Sending...</span>
    </x-ui.button>
</form>
