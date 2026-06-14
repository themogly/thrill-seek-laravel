<?php

namespace App\Http\Controllers\Account;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AccountController
{
    public function index(): View
    {
        $payments = Payment::query()
            ->whereIn('booking_id', $this->customer()->bookings()->select('id'))
            ->with('booking.product')
            ->orderByRaw('coalesce(paid_at, created_at) desc')
            ->get();

        return view('account.payments.index', ['payments' => $payments]);
    }

    /** A printable booking confirmation / receipt — scoped to the owner. */
    public function receipt(Booking $booking): Response
    {
        $booking = $this->ownedBooking($booking)->load(['product', 'payments', 'tandemDate.location', 'courseDate.location']);

        $pdf = Pdf::loadView('pdf.booking-receipt', [
            'booking' => $booking,
            'general' => app(GeneralSettings::class),
            'paidPayments' => $booking->payments->where('status', PaymentStatus::Paid),
        ])->setPaper('a4');

        return $pdf->download('booking-'.$booking->reference.'.pdf');
    }
}
