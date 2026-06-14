<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
}
