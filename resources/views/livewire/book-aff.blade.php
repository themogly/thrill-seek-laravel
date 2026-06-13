<div class="mx-auto max-w-3xl">
    @if ($this->enquirySent)
        <div class="rounded-2xl border bg-card p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary"><x-icon name="check" class="h-7 w-7" /></div>
            <h3 class="mt-4 font-display text-2xl uppercase text-secondary">Course request sent</h3>
            <p class="mt-2 text-muted-foreground">Thanks {{ $name }} — we've got your details and will be in touch shortly to confirm your place and arrange the deposit.</p>
            <x-ui.button href="/" class="mt-6 bg-primary text-primary-foreground hover:bg-primary/90">Back to home</x-ui.button>
        </div>
    @else
    <x-booking.steps :current="$step" :labels="['Pick a course', 'Your details', $this->paymentsEnabled ? 'Review & pay deposit' : 'Review & send']" />

    @if ($unavailableMessage)
        <div class="mt-6">
            <x-booking.notice tone="warning">{{ $unavailableMessage }}</x-booking.notice>
        </div>
    @endif

    {{-- STEP 1: course picker --}}
    @if ($step === 1)
        <div class="mt-8">
            @if ($courses->isEmpty())
                <div class="rounded-2xl border bg-card p-8 text-center">
                    <h3 class="font-display text-2xl uppercase text-secondary">New course dates coming soon</h3>
                    <p class="mt-2 text-muted-foreground">Send an enquiry and we'll let you know the moment the next course opens.</p>
                    <x-ui.button href="/aff#enquiry" class="mt-6 bg-primary text-primary-foreground hover:bg-primary/90">Ask about the next course</x-ui.button>
                </div>
            @else
                <div class="grid gap-4">
                    @foreach ($courses as $course)
                        <button
                            type="button"
                            wire:click="chooseCourse({{ $course->id }})"
                            class="group flex flex-col gap-4 rounded-2xl border bg-card p-6 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-glow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:flex-row sm:items-center sm:justify-between"
                        >
                            <span>
                                <span class="block font-display text-2xl uppercase text-secondary">{{ $course->date_range_label }}</span>
                                <span class="block text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $course->duration_days }}-day course</span>
                                <span class="mt-1 flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-primary">
                                    <x-icon name="map-pin" class="h-4 w-4" /> {{ $course->location->name }}
                                </span>
                                <span class="mt-2 block text-sm text-muted-foreground">
                                    {{ $course->remaining_places }} {{ Str::plural('place', $course->remaining_places) }} left
                                </span>
                            </span>
                            <span class="text-left sm:text-right">
                                <span class="block font-display text-2xl text-secondary">{{ $course->formatted_price }}</span>
                                <span class="block text-sm font-bold text-primary">{{ $course->formatted_deposit }} deposit</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- STEP 2: customer details --}}
    @if ($step === 2)
        <form wire:submit="continueToReview" class="mt-8 rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <h3 class="font-display text-2xl uppercase text-secondary">Your details</h3>
                <button type="button" wire:click="backToStep(1)" class="text-sm font-bold uppercase tracking-wide text-primary hover:underline">
                    Change course
                </button>
            </div>
            @if ($course)
                <p class="mt-1 text-sm text-muted-foreground">{{ $course->date_range_label }} — {{ $course->location->name }}</p>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-booking.field label="Full name" for="ba-name" :error="$errors->first('name')">
                    <x-ui.input id="ba-name" wire:model="name" autocomplete="name" required />
                </x-booking.field>
                <x-booking.field label="Email" for="ba-email" :error="$errors->first('email')">
                    <x-ui.input id="ba-email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
                </x-booking.field>
                <x-booking.field label="Phone" for="ba-phone" :error="$errors->first('phone')">
                    <x-ui.input id="ba-phone" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" required />
                </x-booking.field>
                <x-booking.field label="Date of birth" for="ba-dob" :error="$errors->first('date_of_birth')" hint="You must be 18 or over.">
                    <x-ui.input id="ba-dob" type="date" wire:model="date_of_birth" autocomplete="bday" required />
                </x-booking.field>
                <x-booking.field label="Weight (kg)" for="ba-weight" :error="$errors->first('weight_kg')">
                    <x-ui.input id="ba-weight" type="number" wire:model="weight_kg" inputmode="numeric" required />
                </x-booking.field>
                <x-booking.field label="Emergency contact name" for="ba-ecn" :error="$errors->first('emergency_contact_name')">
                    <x-ui.input id="ba-ecn" wire:model="emergency_contact_name" required />
                </x-booking.field>
                <x-booking.field label="Emergency contact phone" for="ba-ecp" :error="$errors->first('emergency_contact_phone')">
                    <x-ui.input id="ba-ecp" type="tel" wire:model="emergency_contact_phone" inputmode="tel" required />
                </x-booking.field>
                <div class="sm:col-span-2">
                    <x-booking.field label="Any jump experience? (optional)" for="ba-exp" :error="$errors->first('experience')">
                        <x-ui.textarea id="ba-exp" wire:model="experience" rows="3" placeholder="First-timer? Perfect — most of our students are." />
                    </x-booking.field>
                </div>
            </div>

            <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
                <label for="ba-website">Website</label>
                <input id="ba-website" type="text" wire:model="website" tabindex="-1" autocomplete="off" />
            </div>

            <x-ui.button type="submit" size="lg" class="mt-8 w-full bg-primary text-primary-foreground hover:bg-primary/90" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="continueToReview">Review booking</span>
                <span wire:loading wire:target="continueToReview">One moment…</span>
            </x-ui.button>
        </form>
    @endif

    {{-- STEP 3: review & pay deposit --}}
    @if ($step === 3 && $course)
        <div class="mt-8 rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <h3 class="font-display text-2xl uppercase text-secondary">{{ $this->paymentsEnabled ? 'Review & pay deposit' : 'Review & send' }}</h3>
                <button type="button" wire:click="backToStep(2)" class="text-sm font-bold uppercase tracking-wide text-primary hover:underline">
                    Edit details
                </button>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Course</dt>
                    <dd class="font-semibold text-secondary">{{ $course->date_range_label }} ({{ $course->duration_days }} days)</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Location</dt>
                    <dd class="font-semibold text-secondary">{{ $course->location->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Student</dt>
                    <dd class="font-semibold text-secondary">{{ $name }}</dd>
                </div>
                <div class="border-t pt-3"></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Course price</dt>
                    <dd class="font-semibold text-secondary">{{ $course->formatted_price }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t pt-3">
                    <dt class="font-display text-lg uppercase text-secondary">Deposit due now</dt>
                    <dd class="font-display text-2xl text-primary">{{ $course->formatted_deposit }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Balance before the course</dt>
                    <dd class="font-semibold text-secondary">
                        {{ \App\Support\Money::formatPence(max(0, (int) $course->effective_price_pence - (int) $course->effective_deposit_pence)) }}
                    </dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-muted-foreground">
                @if ($this->paymentsEnabled)
                    Your deposit secures the place. We'll send a payment link or bank details for the
                    balance well before the course starts — deposits are transferable if plans change
                    (see the booking terms).
                @else
                    Send us your details and we'll confirm your place and arrange the deposit with you
                    directly — deposits are transferable if plans change (see the booking terms).
                @endif
            </p>

            <label class="mt-6 flex items-start gap-3 text-sm">
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

            <x-ui.button wire:click="pay" size="lg" class="mt-6 w-full bg-primary text-primary-foreground hover:bg-primary/90" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="pay">{{ $this->paymentsEnabled ? 'Pay '.$course->formatted_deposit.' deposit with Stripe' : 'Send course request' }}</span>
                <span wire:loading wire:target="pay">{{ $this->paymentsEnabled ? 'Taking you to secure payment…' : 'Sending your request…' }}</span>
            </x-ui.button>
            @if ($this->paymentsEnabled)
                <p class="mt-3 text-center text-xs text-muted-foreground">Card payments are handled by Stripe — we never see your card details.</p>
            @else
                <p class="mt-3 text-center text-xs text-muted-foreground">We'll confirm your place and arrange the deposit with you directly — no card needed now.</p>
            @endif
        </div>
    @endif
    @endif
</div>
