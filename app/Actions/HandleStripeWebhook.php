<?php

namespace App\Actions;

use Stripe\Event;

/**
 * Thin dispatcher: each Stripe event type the app cares about has its own
 * Action; anything else is acknowledged and ignored.
 */
class HandleStripeWebhook
{
    public function __construct(
        private readonly HandleCheckoutSessionCompleted $completed,
        private readonly HandleCheckoutSessionExpired $expired,
    ) {}

    public function handle(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->completed->handle($event),
            'checkout.session.expired' => $this->expired->handle($event),
            default => null,
        };
    }
}
