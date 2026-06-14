<?php

namespace App\Actions;

use App\Mail\AccountLoginLinkMail;
use App\Models\Customer;
use App\Models\CustomerLoginLink;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Issue a passwordless login link to a customer by email. No-ops silently when
 * no customer matches — the controller always shows the same neutral message, so
 * the response never reveals whether an email is on file.
 */
class RequestAccountLoginLink
{
    public function handle(string $email): void
    {
        $customer = Customer::where('email', mb_strtolower(trim($email)))->first();

        if ($customer === null) {
            return;
        }

        // The 48-char token is itself the secret (stored only as a hash), so the
        // URL needs no extra signing; expiry + single-use live on the link row.
        $token = CustomerLoginLink::issueFor($customer);
        $url = URL::route('account.login.verify', ['token' => $token]);

        Mail::to($customer->email)->queue(new AccountLoginLinkMail($customer, $url));
    }
}
