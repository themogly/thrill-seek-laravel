<?php

namespace App\Services\Newsletter;

use App\Enums\NewsletterStatus;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Database-backed implementation: the subscriber list lives in our tables so
 * consent and unsubscribe are authoritative and auditable; sending uses Resend
 * through the standard queued mail pipeline.
 */
class NativeNewsletterService implements NewsletterService
{
    public function subscribe(string $email, string $source, ?string $name = null): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::firstOrNew([
            'email' => NewsletterSubscriber::normaliseEmail($email),
        ]);

        // Already on the list and confirmed — nothing to do, never duplicate.
        if ($subscriber->exists && $subscriber->status === NewsletterStatus::Confirmed) {
            return $subscriber;
        }

        $subscriber->fill([
            'name' => $name ?: $subscriber->name,
            'status' => NewsletterStatus::Pending,
            'source' => $source,
            'consented_at' => Carbon::now(),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ])->save();

        $this->sendConfirmationEmail($subscriber);

        return $subscriber;
    }

    public function confirm(NewsletterSubscriber $subscriber): void
    {
        $subscriber->markConfirmed();
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): void
    {
        $subscriber->markUnsubscribed();
    }

    private function sendConfirmationEmail(NewsletterSubscriber $subscriber): void
    {
        try {
            Mail::to($subscriber->email)->queue(new NewsletterConfirmationMail($subscriber));
        } catch (\Throwable $e) {
            // Never lose the opt-in because mail is misconfigured — the admin
            // can re-trigger confirmation, and the record is already stored.
            Log::error('Failed to queue newsletter confirmation email', [
                'subscriber_id' => $subscriber->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
