@extends('layouts.app')

@section('title', 'Payment complete — G-Force Skydiving')

@section('content')
    @if ($booking !== null && $payment !== null && $payment->isPaid())
        <x-site.page-hero title="You're Booked!" subtitle="Payment received — check your inbox for the confirmation." />
        <x-site.section>
            <div class="mx-auto max-w-xl">
                <div class="rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-glow">
                            <x-icon name="check" class="h-6 w-6" />
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Booking reference</p>
                            <p class="font-display text-2xl uppercase text-secondary">{{ $booking->reference }}</p>
                        </div>
                    </div>
                    <dl class="mt-6 space-y-3 border-t pt-6 text-sm">
                        @if ($booking->product)
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Booked</dt>
                                <dd class="font-semibold text-secondary">{{ $booking->product->name }}</dd>
                            </div>
                        @endif
                        @if ($booking->courseDate)
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Course</dt>
                                <dd class="font-semibold text-secondary">{{ $booking->courseDate->date_range_label }} — {{ $booking->courseDate->location->name }}</dd>
                            </div>
                        @elseif ($booking->scheduled_at)
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Jump date</dt>
                                <dd class="font-semibold text-secondary">{{ $booking->scheduled_at->format('l j F Y, H:i') }}</dd>
                            </div>
                        @endif
                        @if ($booking->tandemDate?->location)
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Location</dt>
                                <dd class="font-semibold text-secondary">{{ $booking->tandemDate->location->name }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Paid</dt>
                            <dd class="font-semibold text-secondary">{{ $payment->formatted_amount }}</dd>
                        </div>
                        @if ($booking->hasOutstandingBalance())
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Balance to pay later</dt>
                                <dd class="font-semibold text-primary">{{ $booking->formatted_balance_due }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
                <div class="mt-8 rounded-2xl bg-secondary p-6 text-secondary-foreground sm:p-8">
                    <h2 class="font-display text-2xl uppercase">What happens next</h2>
                    <ul class="mt-4 space-y-3 text-sm opacity-95">
                        <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" /> A confirmation email with your reference is on its way.</li>
                        @if ($booking->hasOutstandingBalance())
                            <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" /> We'll send a payment link for the balance well before your course starts.</li>
                        @endif
                        <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" /> Weather looking dodgy? Reschedules for weather are free — we'll keep you posted.</li>
                        <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" /> Questions? Reply to the confirmation email or call us any time.</li>
                    </ul>
                </div>
                <div class="mt-8 text-center">
                    <x-ui.button href="/" size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90">Back to the site</x-ui.button>
                </div>
            </div>
        </x-site.section>
    @elseif ($payment !== null)
        <x-site.page-hero title="Almost There" subtitle="Your payment is being confirmed — this usually takes a few seconds." />
        <x-site.section>
            <div class="mx-auto max-w-xl text-center">
                <p class="text-lg text-muted-foreground">
                    We're waiting for Stripe to confirm your payment. Refresh this page in a moment,
                    or just watch your inbox — your confirmation email will arrive as soon as it clears.
                </p>
                <x-ui.button href="" onclick="window.location.reload(); return false;" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                    Refresh
                </x-ui.button>
            </div>
        </x-site.section>
    @else
        <x-site.page-hero title="Payment Complete" subtitle="Thank you — your payment went through. A confirmation email is on its way." />
        <x-site.section>
            <div class="mx-auto max-w-xl text-center">
                <x-icon name="check" class="mx-auto h-10 w-10 text-primary" />
                <p class="mt-4 text-lg text-muted-foreground">We'll be in touch shortly to arrange the details of your jump.</p>
                <x-ui.button href="/" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                    Back to the site
                </x-ui.button>
            </div>
        </x-site.section>
    @endif
@endsection
