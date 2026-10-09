<div class="max-w-3xl">
    @if ($this->enquirySent)
        <div class="border-2 border-secondary bg-card p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center bg-primary text-primary-foreground"><x-icon name="check" class="h-7 w-7" /></span>
            <h2 class="mt-4 font-display text-2xl uppercase text-secondary">Booking request sent</h2>
            <p class="mt-2 text-muted-foreground">Thanks {{ $name }} — we've got your details and will be in touch shortly to confirm your jump and arrange payment.</p>
            <x-ui.button href="/" class="mt-6">Back to home</x-ui.button>
        </div>
    @else
    <x-booking.steps :current="$step" :labels="['Pick a date', 'Your details', $this->paymentsEnabled ? 'Review & pay' : 'Review & send']" />

    @if ($unavailableMessage)
        <div class="mt-6">
            <x-booking.notice tone="warning">{{ $unavailableMessage }}</x-booking.notice>
        </div>
    @endif

    {{-- STEP 1: slot picker --}}
    @if ($step === 1)
        <div class="mt-8">
            @if ($availableSlots->isEmpty())
                <div class="border-2 border-secondary bg-card p-8 text-center">
                    <h2 class="font-display text-2xl uppercase text-secondary">No dates online right now</h2>
                    <p class="mt-2 text-muted-foreground">We add jump dates all the time. Send an enquiry and we'll find you a slot.</p>
                    <x-ui.button href="/contact" class="mt-6">Get in touch</x-ui.button>
                </div>
            @else
                @php $byLocation = $availableSlots->groupBy(fn ($s) => $s->location->name); @endphp
                @foreach ($byLocation as $locationName => $locationSlots)
                    @if ($byLocation->count() > 1)
                        <h2 class="mb-3 mt-8 flex items-center gap-1.5 font-display text-xl uppercase text-secondary first:mt-0">
                            <x-icon name="map-pin" class="h-5 w-5 text-primary" /> {{ $locationName }}
                        </h2>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2 @if(! $loop->last) mb-2 @endif">
                        @foreach ($locationSlots as $jumpSlot)
                            <button
                                type="button"
                                wire:click="chooseSlot({{ $jumpSlot->id }})"
                                class="group border-2 border-border bg-card p-6 text-left transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <p class="font-display text-2xl uppercase text-secondary">{{ $jumpSlot->starts_at->format('D j M') }}</p>
                                <p class="mt-1 text-sm font-bold uppercase tracking-wide text-primary">{{ $jumpSlot->starts_at->format('H:i') }}</p>
                                @if ($byLocation->count() === 1)
                                    <p class="mt-1 flex items-center gap-1 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                        <x-icon name="map-pin" class="h-3.5 w-3.5" /> {{ $locationName }}
                                    </p>
                                @endif
                                <p class="mt-3 text-sm text-muted-foreground">
                                    {{ $jumpSlot->remaining_capacity }} {{ Str::plural('place', $jumpSlot->remaining_capacity) }} left
                                    @if ($jumpSlot->notes) · {{ $jumpSlot->notes }} @endif
                                </p>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- STEP 2: customer details --}}
    @if ($step === 2)
        <form wire:submit="continueToReview" class="mt-8 border-2 border-secondary bg-card p-6 sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-2xl uppercase text-secondary">Your details</h2>
                <x-ui.button variant="link" wire:click="backToStep(1)">Change date</x-ui.button>
            </div>
            @if ($selectedSlot)
                <p class="mt-1 text-sm text-muted-foreground">
                    Jumping {{ $selectedSlot->starts_at->format('l j F Y \a\t H:i') }} — {{ $selectedSlot->location->name }}
                </p>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-booking.field label="Full name" for="bt-name" :error="$errors->first('name')">
                    <x-ui.input id="bt-name" wire:model="name" autocomplete="name" required />
                </x-booking.field>
                <x-booking.field label="Email" for="bt-email" :error="$errors->first('email')">
                    <x-ui.input id="bt-email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
                </x-booking.field>
                <x-booking.field label="Phone" for="bt-phone" :error="$errors->first('phone')">
                    <x-ui.input id="bt-phone" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" required />
                </x-booking.field>
                <x-booking.field label="Date of birth" for="bt-dob" :error="$errors->first('date_of_birth')" hint="You must be 18 or over.">
                    <x-ui.date-field id="bt-dob" model="date_of_birth" label="Date of birth" :max="now()->subYears(18)->toDateString()" autocomplete="bday" required />
                </x-booking.field>
                <x-booking.field label="Weight (kg)" for="bt-weight" :error="$errors->first('weight_kg')" hint="Needed for kit and weight surcharges — see the tandem page.">
                    <x-ui.input id="bt-weight" type="number" wire:model="weight_kg" inputmode="numeric" required />
                </x-booking.field>
                <x-booking.field label="Emergency contact name" for="bt-ecn" :error="$errors->first('emergency_contact_name')">
                    <x-ui.input id="bt-ecn" wire:model="emergency_contact_name" required />
                </x-booking.field>
                <x-booking.field label="Emergency contact phone" for="bt-ecp" :error="$errors->first('emergency_contact_phone')">
                    <x-ui.input id="bt-ecp" type="tel" wire:model="emergency_contact_phone" inputmode="tel" required />
                </x-booking.field>
                <div class="sm:col-span-2">
                    <x-booking.field label="Anything medical we should know? (optional)" for="bt-medical" :error="$errors->first('medical_notes')">
                        <x-ui.textarea id="bt-medical" wire:model="medical_notes" rows="3" />
                    </x-booking.field>
                </div>
            </div>

            @if ($product && $product->addOns->where('purchasable', true)->isNotEmpty())
                <div class="mt-8">
                    <h3 class="font-display text-xl uppercase text-secondary">Make it unforgettable</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($product->addOns->where('purchasable', true) as $addOn)
                            <label class="flex cursor-pointer items-center justify-between gap-3 border-2 border-border bg-background p-4 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                <span class="flex items-center gap-3">
                                    <input type="checkbox" value="{{ $addOn->id }}" wire:model.live="addOnIds" class="h-5 w-5 rounded border-input text-primary focus:ring-ring" />
                                    <span class="font-semibold text-secondary">{{ $addOn->name }}</span>
                                </span>
                                <span class="font-display text-lg text-primary">{{ $addOn->formatted_price }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
                <label for="bt-website">Website</label>
                <input id="bt-website" type="text" wire:model="website" tabindex="-1" autocomplete="off" />
            </div>

            <x-ui.button type="submit" size="lg" class="mt-8 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="continueToReview">Review booking</span>
                <span wire:loading wire:target="continueToReview">One moment…</span>
            </x-ui.button>
        </form>
    @endif

    {{-- STEP 3: review & pay --}}
    @if ($step === 3 && $product)
        <div class="mt-8 border-2 border-secondary bg-card p-6 sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-2xl uppercase text-secondary">{{ $this->paymentsEnabled ? 'Review & pay' : 'Review & send' }}</h2>
                <x-ui.button variant="link" wire:click="backToStep(2)">Edit details</x-ui.button>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Jump date</dt>
                    <dd class="font-semibold text-secondary">{{ $selectedSlot?->starts_at->format('l j F Y, H:i') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Location</dt>
                    <dd class="font-semibold text-secondary">{{ $selectedSlot?->location->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Jumper</dt>
                    <dd class="font-semibold text-secondary">{{ $name }}</dd>
                </div>
                <div class="border-t pt-3"></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">{{ $product->name }}</dt>
                    <dd class="font-semibold text-secondary">{{ $product->formatted_price }}</dd>
                </div>
                @foreach ($product->addOns->where('purchasable', true)->whereIn('id', $addOnIds) as $addOn)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">{{ $addOn->name }}</dt>
                        <dd class="font-semibold text-secondary">{{ $addOn->formatted_price }}</dd>
                    </div>
                @endforeach
                @if ($appliedVoucher)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Total</dt>
                        <dd class="font-semibold text-secondary">{{ $formattedTotal }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="flex items-center gap-2 text-muted-foreground">
                            Gift voucher {{ $appliedVoucher->code }}
                            <button type="button" wire:click="removeVoucher" class="text-xs font-bold uppercase text-destructive hover:underline">Remove</button>
                        </dt>
                        <dd class="font-semibold text-secondary">−{{ $appliedVoucher->formatted_amount }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 border-t pt-3">
                    <dt class="font-display text-lg uppercase text-secondary">Total due now</dt>
                    <dd class="font-display text-2xl text-primary">{{ $formattedDue }}</dd>
                </div>
            </dl>

            @unless ($appliedVoucher || ! $this->paymentsEnabled)
                <div class="mt-6 border-2 border-border bg-background p-4">
                    <label for="bt-voucher" class="text-sm font-bold uppercase tracking-wide text-secondary">Got a gift voucher?</label>
                    <div class="mt-2 flex gap-2">
                        <x-ui.input id="bt-voucher" wire:model="voucherCode" placeholder="GV-XXXXXXXX" class="flex-1 uppercase" />
                        <x-ui.button type="button" wire:click="applyVoucher" variant="outline"  wire:loading.attr="disabled">
                            Apply
                        </x-ui.button>
                    </div>
                    @if ($voucherMessage)
                        <p class="mt-2 text-sm font-medium text-destructive" role="alert">{{ $voucherMessage }}</p>
                    @endif
                </div>
            @endunless

            <p class="mt-4 text-xs text-muted-foreground">
                P6 third-party insurance and any weight surcharge are paid at the dropzone on the day.
                Weather reschedules are free — see the booking terms.
            </p>

            <label class="mt-6 flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="newsletterOptIn" class="mt-0.5 h-5 w-5 rounded border-input text-primary focus:ring-ring" />
                <span>Keep me posted on jump days, course dates and offers.</span>
            </label>

            <label class="mt-3 flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="terms" class="mt-0.5 h-5 w-5 rounded border-input text-primary focus:ring-ring" />
                <span>I accept the <a href="/terms" target="_blank" class="font-semibold text-primary underline">booking terms</a> and confirm the details above are accurate.</span>
            </label>
            @error('terms')
                <p class="mt-2 text-sm font-medium text-destructive" role="alert">{{ $message }}</p>
            @enderror

            @if ($paymentErrorMessage)
                <div class="mt-4">
                    <x-booking.notice tone="error">{{ $paymentErrorMessage }}</x-booking.notice>
                </div>
            @endif

            <x-ui.button wire:click="pay" size="lg" class="mt-6 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="pay">
                    @if (! $this->paymentsEnabled)
                        Send booking request
                    @elseif ($duePence === 0)
                        Book now — nothing to pay
                    @else
                        Pay {{ $formattedDue }} by card
                    @endif
                </span>
                <span wire:loading wire:target="pay">
                    @if (! $this->paymentsEnabled)
                        Sending your request…
                    @else
                        {{ $duePence === 0 ? 'Confirming your booking…' : 'Taking you to secure payment…' }}
                    @endif
                </span>
            </x-ui.button>
            @if ($this->paymentsEnabled && $duePence > 0)
                <p class="mt-3 text-center text-xs text-muted-foreground">Card payments are secure — we never see your card details.</p>
            @elseif (! $this->paymentsEnabled)
                <p class="mt-3 text-center text-xs text-muted-foreground">We'll confirm availability and arrange payment with you directly — no card needed now.</p>
            @endif
        </div>
    @endif
    @endif
</div>
