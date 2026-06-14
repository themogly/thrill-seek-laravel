<?php

namespace App\Support\Inbound;

use App\Contracts\InboundEmailFetcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a received email's full content from the Resend API. This is the single
 * integration seam against Resend's inbound endpoint — confirm the exact path/shape
 * against the Resend dashboard/docs when wiring the live domain (see SETUP.md). Maps
 * defensively so a missing field degrades to empty rather than throwing.
 */
class ResendInboundEmailFetcher implements InboundEmailFetcher
{
    public function fetch(string $emailId): ?InboundEmail
    {
        $key = (string) config('services.resend.key');

        if ($key === '') {
            Log::warning('Inbound email fetch skipped: RESEND_API_KEY not set.');

            return null;
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->get("https://api.resend.com/emails/{$emailId}");

        if (! $response->successful()) {
            Log::warning('Inbound email fetch failed.', ['id' => $emailId, 'status' => $response->status()]);

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        $headers = [];
        if (is_array($data['headers'] ?? null)) {
            foreach ($data['headers'] as $name => $value) {
                $headers[strtolower((string) $name)] = is_scalar($value) ? (string) $value : '';
            }
        }

        $attachments = [];
        if (is_array($data['attachments'] ?? null)) {
            foreach ($data['attachments'] as $attachment) {
                if (! is_array($attachment)) {
                    continue;
                }
                $attachments[] = [
                    'filename' => (string) ($attachment['filename'] ?? 'attachment'),
                    'content_type' => (string) ($attachment['content_type'] ?? 'application/octet-stream'),
                    'content' => (string) ($attachment['content'] ?? ''),
                ];
            }
        }

        return new InboundEmail(
            text: (string) ($data['text'] ?? ''),
            html: isset($data['html']) ? (string) $data['html'] : null,
            headers: $headers,
            attachments: $attachments,
            receivedAt: isset($data['created_at']) ? Carbon::parse((string) $data['created_at']) : null,
        );
    }
}
