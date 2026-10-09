<?php

namespace App\Mail;

use App\Mail\Concerns\EmbedsMailLogo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Every customer/owner email that goes through the queue extends this, so the
 * delivery rules live in one place (enforced by MailInventoryTest):
 *
 * - retries with backoff — Horizon's supervisor runs tries => 1, so without this
 *   one Resend blip loses the email for good;
 * - queued after commit — every mailable serialises a model, and a worker must
 *   never pick it up before the row exists or after a rollback;
 * - a finally-failed send is logged by type (never the address) and shows on the
 *   dashboard's mail-health widget.
 */
abstract class QueuedMailable extends Mailable implements ShouldQueueAfterCommit
{
    use EmbedsMailLogo, Queueable, SerializesModels;

    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [30, 120, 600];

    public function failed(Throwable $e): void
    {
        Log::error('Email failed after all retries', [
            'mailable' => static::class,
            'exception' => $e->getMessage(),
        ]);
    }
}
