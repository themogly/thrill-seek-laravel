<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Shared spam protection for the public enquiry forms: a honeypot field
 * (bots fill it, humans never see it) and a per-IP rate limit.
 */
trait SubmitsEnquiries
{
    /** Honeypot — must stay empty. */
    public string $website = '';

    protected function isSpam(): bool
    {
        return $this->website !== '';
    }

    protected function ensureNotRateLimited(): void
    {
        $key = 'enquiries:'.sha1((string) request()->ip());

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $message = 'Too many messages — please wait a few minutes and try again.';
            $this->dispatch('enquiry-failed', message: $message);

            throw ValidationException::withMessages(['name' => $message]);
        }

        RateLimiter::hit($key, decaySeconds: 600);
    }

    /**
     * Validate, converting failures into a toast so the public design needs
     * no inline error markup.
     *
     * @return array<string, mixed>
     */
    protected function validateForToast(): array
    {
        try {
            return $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('enquiry-failed', message: collect($e->errors())->flatten()->first());

            throw $e;
        }
    }
}
