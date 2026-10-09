<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Public double-opt-in confirmation and one-click unsubscribe. Every route is
 * signed (no login, tamper-proof); the controller delegates to the service and
 * returns a Blade confirmation page in the site design, or, for the mail
 * client's RFC 8058 POST, a bare 200.
 */
class NewsletterController extends Controller
{
    public function confirm(NewsletterSubscriber $subscriber, NewsletterService $newsletter): View
    {
        $newsletter->confirm($subscriber);

        return view('pages.newsletter-status', [
            'heading' => "You're in!",
            'message' => "Thanks for confirming — you'll be the first to hear about jump days, course dates and offers.",
        ]);
    }

    public function unsubscribe(NewsletterSubscriber $subscriber, NewsletterService $newsletter): View
    {
        $newsletter->unsubscribe($subscriber);

        return view('pages.newsletter-status', [
            'heading' => 'Unsubscribed',
            'message' => "You've been removed from the G-Force newsletter and won't receive any more emails. Changed your mind? You can sign up again any time.",
        ]);
    }

    /** The mail client's one-click POST (List-Unsubscribe-Post). Idempotent. */
    public function unsubscribeOneClick(NewsletterSubscriber $subscriber, NewsletterService $newsletter): Response
    {
        $newsletter->unsubscribe($subscriber);

        return response('Unsubscribed.', 200, ['Content-Type' => 'text/plain']);
    }
}
