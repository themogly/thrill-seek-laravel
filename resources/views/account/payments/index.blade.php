@extends('layouts.app')

@section('title', 'Payments — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-site.page-hero title="Payments" subtitle="Everything you've paid us." />

    <x-site.section>
        <x-account.nav />

        @if ($payments->isEmpty())
            <p class="mt-8 text-muted-foreground">No payments yet.</p>
        @else
            <x-ui.table-scroll label="Payments" class="mt-8 border-2 border-border">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b-2 border-border text-xs uppercase tracking-widest text-muted-foreground">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">For</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="border-b border-border last:border-0">
                                <td class="px-4 py-3">{{ ($payment->paid_at ?? $payment->created_at)?->format('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    {{ $payment->description ?? $payment->purpose->getLabel() }}
                                    @if ($payment->booking)
                                        <span class="block text-xs text-muted-foreground">{{ $payment->booking->reference }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $payment->method->getLabel() }}</td>
                                <td class="px-4 py-3">{{ $payment->status->getLabel() }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ $payment->formatted_amount }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-scroll>
        @endif
    </x-site.section>
@endsection
