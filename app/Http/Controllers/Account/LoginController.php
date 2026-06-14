<?php

namespace App\Http\Controllers\Account;

use App\Actions\RequestAccountLoginLink;
use App\Http\Controllers\Controller;
use App\Models\CustomerLoginLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Passwordless magic-link sign-in for the customer account area. The response to a
 * link request is ALWAYS the same neutral message, so it never reveals whether an
 * email is on file.
 */
class LoginController extends Controller
{
    private const NEUTRAL = "If you've booked with us, we've emailed you a secure sign-in link. Please check your inbox (and spam).";

    public function show(): View
    {
        return view('account.login');
    }

    public function sendLink(Request $request, RequestAccountLoginLink $action): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        // Rate-limit per email+IP; on limit, still show the neutral message.
        $key = 'account-login:'.Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('account_status', self::NEUTRAL);
        }

        RateLimiter::hit($key, 900);
        $action->handle($data['email']);

        return back()->with('account_status', self::NEUTRAL);
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $link = CustomerLoginLink::findValid($token);

        if ($link === null) {
            return redirect()->route('account.login')
                ->with('account_error', 'That sign-in link has expired or has already been used. Please request a new one.');
        }

        $link->consume();
        $customer = $link->customer;

        Auth::guard('customer')->login($customer);
        $customer->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', "You've been signed out.");
    }
}
