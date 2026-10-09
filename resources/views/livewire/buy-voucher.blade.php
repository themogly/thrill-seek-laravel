<div class="mx-auto max-w-3xl">
    @if ($this->enquirySent)
        <div class="border-2 border-secondary bg-card p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center bg-primary text-primary-foreground"><x-icon name="check" class="h-7 w-7" /></span>
            <h2 class="mt-4 font-display text-2xl uppercase text-secondary">Voucher request sent</h2>
            <p class="mt-2 text-muted-foreground">Thanks {{ $purchaser_name }} — we've got your request and will be in touch shortly to arrange the voucher and payment.</p>
            <x-ui.button href="/" class="mt-6">Back to home</x-ui.button>
        </div>
    @elseif ($product === null || $product->price_pence === null)
        <div class="border-2 border-secondary bg-card p-8 text-center">
            <h2 class="font-display text-2xl uppercase text-secondary">Vouchers are taking a breather</h2>
            <p class="mt-2 text-muted-foreground">Get in touch and we'll arrange one directly.</p>
            <x-ui.button href="/contact" class="mt-6">Contact us</x-ui.button>
        </div>
    @else
        <form wire:submit="pay" class="border-2 border-secondary bg-card p-6 sm:p-8">
            <div class="flex flex-col gap-4 border-t-4 border-primary bg-secondary p-6 text-secondary-foreground sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide opacity-80">The gift</p>
                    <p class="font-display text-3xl uppercase">{{ $product->name }}</p>
                    <p class="mt-1 text-sm opacity-90">Valid 12 months · transferable · redeemable online</p>
                </div>
                <p class="font-display text-4xl text-sky-bright">{{ $product->formatted_price }}</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-booking.field label="Your name" for="bv-name" :error="$errors->first('purchaser_name')">
                    <x-ui.input id="bv-name" wire:model="purchaser_name" autocomplete="name" required />
                </x-booking.field>
                <x-booking.field label="Your email" for="bv-email" :error="$errors->first('purchaser_email')" hint="The voucher is emailed here.">
                    <x-ui.input id="bv-email" type="email" wire:model="purchaser_email" autocomplete="email" inputmode="email" required />
                </x-booking.field>
                <div class="sm:col-span-2">
                    <x-booking.field label="Who's it for?" for="bv-recipient" :error="$errors->first('recipient_name')">
                        <x-ui.input id="bv-recipient" wire:model="recipient_name" required />
                    </x-booking.field>
                </div>
                <div class="sm:col-span-2">
                    <x-booking.field label="A personal message (optional)" for="bv-message" :error="$errors->first('message')" hint="Included on the voucher email.">
                        <x-ui.textarea id="bv-message" wire:model="message" rows="3" placeholder="Happy birthday! Time to jump out of a plane…" />
                    </x-booking.field>
                </div>
            </div>

            <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
                <label for="bv-website">Website</label>
                <input id="bv-website" type="text" wire:model="website" tabindex="-1" autocomplete="off" />
            </div>

            <label class="mt-6 flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="newsletterOptIn" class="mt-0.5 h-5 w-5 rounded border-input text-primary focus:ring-ring" />
                <span>Keep me posted on jump days, course dates and offers.</span>
            </label>

            <label class="mt-3 flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="terms" class="mt-0.5 h-5 w-5 rounded border-input text-primary focus:ring-ring" />
                <span>I accept the <a href="/terms" target="_blank" class="font-semibold text-primary-strong underline">voucher terms</a> — valid 12 months, transferable, non-refundable.</span>
            </label>
            @error('terms')
                <p class="mt-2 text-sm font-medium text-destructive" role="alert">{{ $message }}</p>
            @enderror

            @if ($paymentErrorMessage)
                <div class="mt-4">
                    <x-booking.notice tone="error">{{ $paymentErrorMessage }}</x-booking.notice>
                </div>
            @endif

            <x-ui.button type="submit" size="lg" class="mt-6 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="pay">{{ $this->paymentsEnabled ? 'Buy for '.$product->formatted_price.' — delivered by email' : 'Request this voucher' }}</span>
                <span wire:loading wire:target="pay">{{ $this->paymentsEnabled ? 'Taking you to secure payment…' : 'Sending your request…' }}</span>
            </x-ui.button>
            @if ($this->paymentsEnabled)
                <p class="mt-3 text-center text-xs text-muted-foreground">Card payments are secure — we never see your card details.</p>
            @else
                <p class="mt-3 text-center text-xs text-muted-foreground">We'll arrange the voucher and payment with you directly — no card needed now.</p>
            @endif
        </form>
    @endif
</div>
