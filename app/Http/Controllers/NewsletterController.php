<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Contracts\View\View;

/**
 * Public double-opt-in confirmation and one-click unsubscribe. Both routes are
 * signed (no login, tamper-proof); the controller delegates to the service and
 * returns a Blade confirmation page in the site design.
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
}
