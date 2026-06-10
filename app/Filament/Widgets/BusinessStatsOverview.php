<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenueThisMonth = (int) Payment::where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount_pence');

        $newEnquiries = Enquiry::where('status', EnquiryStatus::New)->count();

        $upcomingJumps = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Rescheduled])
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->count();

        $outstanding = Booking::withOutstandingBalance()
            ->whereNotIn('status', [BookingStatus::Cancelled])
            ->get()
            ->sum(fn (Booking $booking): int => $booking->balance_due_pence);

        return [
            Stat::make('Revenue this month', Money::formatPence($revenueThisMonth))
                ->description('Paid payments, all methods')
                ->color('success'),
            Stat::make('New enquiries', (string) $newEnquiries)
                ->description('Awaiting a first reply')
                ->color($newEnquiries > 0 ? 'warning' : 'gray'),
            Stat::make('Jumps in the next 7 days', (string) $upcomingJumps)
                ->description('Confirmed or rescheduled')
                ->color('info'),
            Stat::make('Outstanding balances', Money::formatPence((int) $outstanding))
                ->description('Across active bookings')
                ->color($outstanding > 0 ? 'danger' : 'success'),
        ];
    }
}
