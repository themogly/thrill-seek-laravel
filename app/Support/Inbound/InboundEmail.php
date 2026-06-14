<?php

namespace App\Support\Inbound;

use Illuminate\Support\Carbon;

/**
 * The full content of an inbound email, fetched in the second Resend call. Plain
 * value object so the fetcher seam is trivial to fake in tests.
 */
class InboundEmail
{
    /**
     * @param  array<string, string>  $headers  lower-cased header name => value
     * @param  list<array{filename: string, content_type: string, content: string}>  $attachments
     */
    public function __construct(
        public readonly string $text,
        public readonly ?string $html = null,
        public readonly array $headers = [],
        public readonly array $attachments = [],
        public readonly ?Carbon $receivedAt = null,
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
