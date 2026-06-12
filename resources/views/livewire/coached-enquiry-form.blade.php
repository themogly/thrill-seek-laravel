<form
    wire:submit="submit"
    x-data
    @enquiry-sent.window="window.toast.success($event.detail.message); $dispatch('reset')"
    @enquiry-failed.window="window.toast.error($event.detail.message)"
    class="border-2 border-secondary bg-card p-6 sm:p-8"
>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-booking.field label="Full name" for="ce-name" :error="$errors->first('name')">
            <x-ui.input id="ce-name" wire:model="name" autocomplete="name" required />
        </x-booking.field>
        <x-booking.field label="Email" for="ce-email" :error="$errors->first('email')">
            <x-ui.input id="ce-email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
        </x-booking.field>
        <x-booking.field label="Phone" for="ce-phone" :error="$errors->first('phone')">
            <x-ui.input id="ce-phone" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" required />
        </x-booking.field>
        <x-booking.field label="What do you want to work on?" for="ce-discipline" :error="$errors->first('discipline')">
            <select
                id="ce-discipline"
                wire:model="discipline"
                required
                class="flex h-11 w-full items-center justify-between whitespace-nowrap border-2 border-input bg-transparent px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-ring"
            >
                <option value="">Pick a discipline</option>
                @foreach (\App\Livewire\CoachedEnquiryForm::DISCIPLINES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </x-booking.field>
        <x-booking.field label="Roughly how many jumps?" for="ce-jumps" :error="$errors->first('jumps')">
            <x-ui.input id="ce-jumps" type="number" wire:model="jumps" inputmode="numeric" required />
        </x-booking.field>
        <x-booking.field label="Licence (if any)" for="ce-licence" :error="$errors->first('licence')" hint="e.g. A, B, C — leave blank if none yet.">
            <x-ui.input id="ce-licence" wire:model="licence" />
        </x-booking.field>
        <div class="sm:col-span-2">
            <x-booking.field label="Anything else we should know? (optional)" for="ce-msg" :error="$errors->first('message')">
                <x-ui.textarea id="ce-msg" wire:model="message" rows="3" placeholder="Goals, dates that suit you, video links…" />
            </x-booking.field>
        </div>
    </div>

    <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
        <label for="ce-website">Website</label>
        <input id="ce-website" type="text" wire:model="website" tabindex="-1" autocomplete="off" />
    </div>

    <x-ui.button type="submit" size="lg" class="mt-6 w-full" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="submit">Get a coaching plan</span>
        <span wire:loading wire:target="submit">Sending…</span>
    </x-ui.button>
</form>
