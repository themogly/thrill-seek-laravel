<?php

namespace App\Support\Inbound;

/**
 * Verifies the Svix signature Resend puts on its webhooks. Signed content is
 * `{svix-id}.{svix-timestamp}.{rawBody}`, HMAC-SHA256 with the base64 secret (the
 * part after the `whsec_` prefix); the `svix-signature` header is a space-separated
 * list of `v1,<base64sig>` entries, any of which may match. Constant-time compared.
 */
class ResendWebhookSignature
{
    public static function isValid(string $rawBody, string $id, string $timestamp, string $signatureHeader, string $secret): bool
    {
        if ($id === '' || $timestamp === '' || $signatureHeader === '' || $secret === '') {
            return false;
        }

        $key = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $keyBytes = base64_decode($key, true);
        if ($keyBytes === false) {
            return false;
        }

        $signedContent = "{$id}.{$timestamp}.{$rawBody}";
        $expected = base64_encode(hash_hmac('sha256', $signedContent, $keyBytes, true));

        foreach (explode(' ', $signatureHeader) as $entry) {
            // Each entry is "v1,<signature>".
            $parts = explode(',', $entry, 2);
            $candidate = $parts[1] ?? $parts[0];

            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
