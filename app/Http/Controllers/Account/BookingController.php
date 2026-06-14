<?php

namespace App\Http\Controllers\Account;

use App\Actions\StartBalanceCheckout;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BookingController extends AccountController
{
    public function index(): View
    {
        $bookings = $this->customer()->bookings()
            ->with(['product', 'payments', 'tandemDate.location', 'courseDate.location'])
            ->get();

        // Upcoming = a future scheduled date; everything else is past/awaiting.
        [$upcoming, $past] = $bookings->partition(
            fn (Booking $b): bool => $b->scheduled_at !== null && $b->scheduled_at->isFuture(),
        );

        return view('account.bookings.index', [
            'upcoming' => $upcoming->sortBy('scheduled_at')->values(),
            'past' => $past->sortByDesc(fn (Booking $b) => $b->scheduled_at ?? $b->created_at)->values(),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking = $this->ownedBooking($booking)->load(['product', 'payments', 'tandemDate.location', 'courseDate.location']);

        return view('account.bookings.show', ['booking' => $booking]);
    }

    public function pay(Booking $booking, StartBalanceCheckout $checkout): RedirectResponse
    {
        $booking = $this->ownedBooking($booking);

        if (! $booking->hasOutstandingBalance()) {
            return redirect()->route('account.bookings.show', $booking)
                ->with('account_status', 'That booking is already paid in full.');
        }

        return redirect()->away($checkout->handle($booking));
    }
}
