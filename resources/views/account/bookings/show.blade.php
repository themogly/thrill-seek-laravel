@extends('layouts.app')

@section('title', $booking->reference.' — My Booking')
@section('robots', 'noindex, nofollow')

@php use App\Support\Money; @endphp

@section('content')
    <x-site.page-hero :title="$booking->product?->name ?? 'Your booking'" :subtitle="'Reference '.$booking->reference" />

    <x-site.section>
        <x-account.nav />

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            {{-- Details --}}
            <div class="border-2 border-border p-6 lg:col-span-2">
                <h2 class="font-display text-2xl uppercase tracking-wide">Jump details</h2>
                <dl class="mt-4 grid grid-cols-[auto,1fr] gap-x-6 gap-y-2 text-ink">
                    <dt class="font-bold uppercase tracking-widest text-secondary">Date</dt>
                    <dd>{{ $booking->scheduledLabel() ?? 'To be confirmed' }}</dd>
                    @if ($booking->locationName())
                        <dt class="font-bold uppercase tracking-widest text-secondary">Where</dt>
                        <dd>{{ $booking->locationName() }}</dd>
                    @endif
                    <dt class="font-bold uppercase tracking-widest text-secondary">Status</dt>
                    <dd>{{ $booking->status->getLabel() }}</dd>
                </dl>

                <h2 class="mt-8 font-display text-2xl uppercase tracking-wide">Payments</h2>
                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b-2 border-border text-xs uppercase tracking-widest text-muted-foreground">
                            <th class="py-2">Date</th><th class="py-2">For</th><th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($booking->payments->where('status', \App\Enums\PaymentStatus::Paid) as $payment)
                            <tr class="border-b border-border">
                                <td class="py-2">{{ $payment->paid_at?->format('j M Y') ?? '—' }}</td>
                                <td class="py-2">{{ $payment->purpose->getLabel() }}</td>
                                <td class="py-2 text-right font-bold">{{ $payment->formatted_amount }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-muted-foreground">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Balance / pay --}}
            <div class="border-2 p-6 {{ $booking->awaitingBalance() ? 'border-primary bg-sky-bright/5' : 'border-border' }}">
                <h2 class="font-display text-2xl uppercase tracking-wide">Balance</h2>
                <dl class="mt-4 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-muted-foreground">Total</dt><dd class="font-bold">{{ Money::formatPence($booking->price_pence) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted-foreground">Paid</dt><dd class="font-bold">{{ Money::formatPence($booking->total_paid_pence) }}</dd></div>
                </dl>
                @if ($booking->awaitingBalance())
                    <p class="mt-4 text-sm text-muted-foreground">Outstanding</p>
                    <p class="font-display text-3xl text-primary">{{ $booking->formatted_balance_due }}</p>
                    <form method="POST" action="{{ route('account.bookings.pay', $booking) }}" class="mt-4">
                        @csrf
                        <x-ui.button type="submit" class="w-full">Pay {{ $booking->formatted_balance_due }} by card</x-ui.button>
                    </form>
                    <p class="mt-3 text-xs text-muted-foreground">You'll be taken to our secure card-payment page.</p>
                @elseif (! $booking->hasOutstandingBalance())
                    <p class="mt-4 font-bold uppercase tracking-widest text-secondary">Paid in full 🪂</p>
                @endif
            </div>
        </div>

        @if ($booking->isTandem() && $booking->scheduled_at?->isFuture())
            {{-- Tandem only — see Booking::isTandem(). --}}
            @inject('jumpPrep', 'App\Settings\JumpPrepSettings')
            <div class="mt-6 border-2 border-border p-6">
                <h2 class="font-display text-2xl uppercase tracking-wide">Before your jump</h2>
                <div class="mt-4 grid gap-6 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-primary">Arrival &amp; timing</p>
                        <p class="mt-2 text-sm text-secondary">{{ $jumpPrep->arrival_info }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-primary">What to bring</p>
                        <p class="mt-2 text-sm text-secondary">{{ $jumpPrep->what_to_bring }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-primary">What to expect</p>
                        <p class="mt-2 text-sm text-secondary">{{ $jumpPrep->what_to_expect }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
            <x-ui.button variant="link" :href="route('account.bookings')">&larr; All bookings</x-ui.button>
            <x-ui.button variant="outline" size="sm" :href="route('account.bookings.receipt', $booking)">Download receipt (PDF)</x-ui.button>
        </div>
    </x-site.section>
@endsection
