<?php

namespace App\Http\Controllers\Account;

use App\Actions\StartBalanceCheckout;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class BookingController extends AccountController
{
    /** Group order shown on the page; empty groups are hidden. */
    private const GROUP_ORDER = ['Upcoming', 'Awaiting a date', 'Awaiting payment', 'Past'];

    public function index(): View
    {
        $bookings = $this->customer()->bookings()
            ->with(['product', 'payments', 'tandemDate.location', 'courseDate.location'])
            ->get();

        $grouped = $bookings->groupBy(fn (Booking $b): string => $this->groupFor($b));

        $groups = collect(self::GROUP_ORDER)
            ->filter(fn (string $label): bool => $grouped->get($label)?->isNotEmpty() ?? false)
            ->mapWithKeys(fn (string $label): array => [$label => $this->sortGroup($label, $grouped[$label])])
            ->all();

        return view('account.bookings.index', ['groups' => $groups]);
    }

    /** Put each booking in exactly one self-explanatory bucket. */
    private function groupFor(Booking $booking): string
    {
        if (in_array($booking->status, [BookingStatus::Completed, BookingStatus::Cancelled], true)) {
            return 'Past';
        }

        if ($booking->status === BookingStatus::PendingPayment) {
            return 'Awaiting payment';
        }

        if ($booking->scheduled_at?->isFuture()) {
            return 'Upcoming';
        }

        return $booking->scheduled_at === null ? 'Awaiting a date' : 'Past';
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return Collection<int, Booking>
     */
    private function sortGroup(string $label, Collection $bookings): Collection
    {
        return $label === 'Upcoming'
            ? $bookings->sortBy('scheduled_at')->values()
            : $bookings->sortByDesc(fn (Booking $b) => $b->scheduled_at ?? $b->created_at)->values();
    }

    public function show(Booking $booking): View
    {
        $booking = $this->ownedBooking($booking)->load(['product', 'payments', 'tandemDate.location', 'courseDate.location']);

        return view('account.bookings.show', ['booking' => $booking]);
    }

    public function pay(Booking $booking, StartBalanceCheckout $checkout): RedirectResponse
    {
        $booking = $this->ownedBooking($booking);

        if (! $booking->awaitingBalance()) {
            return redirect()->route('account.bookings.show', $booking)
                ->with('account_status', 'There is nothing to pay on that booking.');
        }

        return redirect()->away($checkout->handle($booking));
    }
}
