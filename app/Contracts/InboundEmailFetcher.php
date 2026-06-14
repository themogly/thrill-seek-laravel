<?php

namespace App\Contracts;

use App\Support\Inbound\InboundEmail;

/**
 * The second step of Resend inbound: the webhook only carries metadata, so the full
 * body + attachments are fetched separately by the email id. Behind an interface so
 * tests fake it and never hit the real API.
 */
interface InboundEmailFetcher
{
    public function fetch(string $emailId): ?InboundEmail;
}
