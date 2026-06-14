<?php

namespace App\Jobs;

use App\Actions\HandleInboundEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs the heavy inbound work (the second Resend fetch + threading) off the request
 * so the webhook returns fast. The payload is the already-verified webhook body.
 */
class ProcessInboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  array<string, mixed>  $payload */
    public function __construct(public readonly array $payload) {}

    public function handle(HandleInboundEmail $action): void
    {
        $action->handle($this->payload);
    }
}
