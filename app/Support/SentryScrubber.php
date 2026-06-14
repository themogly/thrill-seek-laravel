<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Privacy-first `before_send` hook for Sentry, so error tracking can never become
 * a second, unaudited store of customer PII.
 *
 * `send_default_pii` is already false (config/sentry.php), so Sentry won't attach
 * IPs, cookies or the authenticated user by default. This scrubber is the
 * belt-and-braces second layer: it drops request bodies wholesale (that is where
 * the booking/enquiry forms put names, DOB, weight, height, sex, medical notes and
 * addresses) and recursively redacts any key that looks personal or secret from
 * the request, query string and our own `extra` context.
 *
 * Wired as an array callable (not a closure) so `php artisan config:cache` can
 * still serialize the config. See DECISIONS.md (SEC-P3.3).
 */
class SentryScrubber
{
    private const REDACTED = '[redacted]';

    /**
     * Substrings matched (case-insensitively) against array KEYS. A match redacts
     * the value. Over-redaction is the safe failure mode for an error tracker.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'name', 'email', 'phone', 'address', 'postcode', 'post_code', 'city',
        'dob', 'date_of_birth', 'birth', 'weight', 'height', 'sex', 'gender',
        'medical', 'notes', 'message', 'body',
        'password', 'secret', 'token', 'authorization', 'cookie', 'csrf',
        'card', 'cvc', 'cvv', 'client_secret', 'payment_intent', 'checkout_session',
    ];

    public static function scrub(Event $event, ?EventHint $hint = null): ?Event
    {
        $scrubber = new self;

        $request = $event->getRequest();
        if ($request !== []) {
            // The request body is where form PII lives — never ship it, on any route.
            unset($request['data']);
            $request['cookies'] = [];
            if (isset($request['query_string']) && is_string($request['query_string'])) {
                $request['query_string'] = $scrubber->redactQueryString($request['query_string']);
            }
            $event->setRequest($scrubber->redact($request));
        }

        $event->setExtra($scrubber->redact($event->getExtra()));

        return $event;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = mb_strtolower($key);

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function redactQueryString(string $queryString): string
    {
        parse_str($queryString, $params);

        return http_build_query($this->redact($params));
    }
}
