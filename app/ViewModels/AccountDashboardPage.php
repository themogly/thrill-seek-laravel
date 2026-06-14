<?php

namespace App\ViewModels;

use App\Enums\PaymentStatus;
use App\Enums\VoucherStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Voucher;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Dashboard summary for the signed-in customer: their next jump, what they owe, and
 * recent activity. Everything is scoped to the given customer — never the URL.
 */
class AccountDashboardPage
{
    /** @return array<string, mixed> */
    public function viewData(Customer $customer): array
    {
        $upcoming = $customer->bookings()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->with(['product', 'tandemDate.location', 'courseDate.location', 'payments'])
            ->reorder('scheduled_at')
            ->first();

        /** @var Collection<int, Booking> $outstanding */
        $outstanding = $customer->bookings()
            ->withOutstandingBalance()
            ->with(['product', 'payments'])
            ->get();

        $totalOutstanding = (int) $outstanding->sum(fn (Booking $b): int => $b->balance_due_pence);

        $recentPayments = Payment::query()
            ->whereIn('booking_id', $customer->bookings()->select('id'))
            ->where('status', PaymentStatus::Paid)
            ->with('booking.product')
            ->latest('paid_at')
            ->limit(5)
            ->get();

        // Vouchers the customer bought that are still redeemable.
        $vouchers = Voucher::query()
            ->where('purchaser_email', $customer->email)
            ->where('status', VoucherStatus::Active)
            ->get()
            ->filter(fn (Voucher $v): bool => $v->isRedeemable())
            ->values();

        return [
            'customer' => $customer,
            'upcoming' => $upcoming,
            'outstandingBookings' => $outstanding,
            'totalOutstandingPence' => $totalOutstanding,
            'totalOutstandingLabel' => Money::formatPence($totalOutstanding),
            'recentPayments' => $recentPayments,
            'canReview' => $customer->canLeaveReview(),
            'vouchers' => $vouchers,
        ];
    }
}
