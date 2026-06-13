<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscriber;

/**
 * List-management contract for the newsletter. We keep the authoritative
 * subscriber list (with consent and unsubscribe timestamps) in our own
 * database — see DECISIONS.md — and send through Resend via the normal mail
 * pipeline. This interface isolates the list provider so a future move to
 * Resend Audiences (or another platform) is a single binding swap.
 */
interface NewsletterService
{
    /**
     * Register an opt-in request. Idempotent: an already-confirmed address is
     * returned untouched; a new, pending or previously-unsubscribed address is
     * (re)set to pending and sent a fresh double-opt-in confirmation email.
     */
    public function subscribe(string $email, string $source, ?string $name = null): NewsletterSubscriber;

    /** Complete double opt-in — the subscriber clicked the emailed link. */
    public function confirm(NewsletterSubscriber $subscriber): void;

    /** Immediate, recorded unsubscribe — never sent to again. */
    public function unsubscribe(NewsletterSubscriber $subscriber): void;
}
