<?php

namespace App\Livewire\Concerns;

use App\Models\NewsletterSubscriber;

/**
 * Shared "keep me posted" opt-in for the booking/voucher flows. The checkbox
 * defaults to unticked — consent must be active, never pre-ticked — and a ticked
 * box routes the customer through the same newsletter backend the footer uses
 * (no second signup path).
 */
trait OffersNewsletterOptIn
{
    /** Unticked by default — explicit opt-in only. */
    public bool $newsletterOptIn = false;

    protected function subscribeIfOptedIn(string $email): void
    {
        if ($this->newsletterOptIn) {
            NewsletterSubscriber::subscribe($email);
        }
    }
}
