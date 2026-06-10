<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Payment;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(private readonly SiteContent $content) {}

    public function home(): View
    {
        return view('pages.home', [
            'instructors' => $this->content->instructors(),
            'testimonials' => $this->content->featuredTestimonials(),
            'galleryImages' => $this->content->galleryImages(),
            'services' => $this->content->homeServices(),
        ]);
    }

    public function tandem(): View
    {
        return view('pages.tandem', [
            'product' => $this->content->tandemProduct(),
        ]);
    }

    public function aff(): View
    {
        return view('pages.aff', [
            'products' => $this->content->affProducts(),
            // Live query (not cached): remaining places must always be current.
            'courseDates' => CourseDate::upcomingOpen()->with('product')->get(),
        ]);
    }

    public function coached(): View
    {
        return view('pages.coached');
    }

    public function shop(): View
    {
        return view('pages.shop', [
            'items' => $this->content->shopItems(),
        ]);
    }

    public function testimonials(): View
    {
        return view('pages.testimonials', [
            'testimonials' => $this->content->allTestimonials(),
        ]);
    }

    public function hallOfFame(): View
    {
        return view('pages.hall-of-fame', [
            'entries' => $this->content->hallOfFame(),
        ]);
    }

    public function bookTandem(): View
    {
        return view('pages.book-tandem');
    }

    public function bookAff(): View
    {
        return view('pages.book-aff');
    }

    public function vouchers(): View
    {
        return view('pages.vouchers');
    }

    /**
     * Stripe redirects here with the Checkout session id, so the page can
     * show the customer their booking (or a processing note while the
     * webhook catches up).
     */
    public function paymentSuccess(Request $request): View
    {
        $payment = null;
        $booking = null;

        if (is_string($sessionId = $request->query('session_id')) && $sessionId !== '') {
            $payment = Payment::with(['booking.product', 'booking.courseDate', 'booking.tandemDate'])
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
            $booking = $payment?->booking;
        } elseif (is_string($reference = $request->query('booking')) && $reference !== '') {
            // Voucher-covered bookings skip Stripe entirely; the unguessable
            // booking reference acts as the claim check.
            $booking = Booking::with(['product', 'courseDate', 'tandemDate'])
                ->where('reference', strtoupper($reference))
                ->first();
            $payment = $booking?->payments()->where('status', PaymentStatus::Paid)->latest('id')->first();
        }

        return view('pages.payment-success', [
            'payment' => $payment,
            'booking' => $booking,
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }
}
