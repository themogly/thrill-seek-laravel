<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Support\Facades\Auth;

/**
 * Base for every signed-in account screen. `customer()` is the ONLY source of the
 * current customer — every query scopes to it, so a record id from the URL can
 * never grant access to another customer's data.
 */
abstract class AccountController extends Controller
{
    protected function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }

    /**
     * Guard route-model-bound records: a booking only belongs to the signed-in
     * customer or it's a 404 (never reveal another customer's booking exists).
     */
    protected function ownedBooking(Booking $booking): Booking
    {
        abort_unless($booking->customer_id === $this->customer()->getKey(), 404);

        return $booking;
    }

    protected function ownedEnquiry(Enquiry $enquiry): Enquiry
    {
        abort_unless($enquiry->customer_id === $this->customer()->getKey(), 404);

        return $enquiry;
    }
}
