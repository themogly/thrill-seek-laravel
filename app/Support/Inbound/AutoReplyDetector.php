<?php

namespace App\Support\Inbound;

/**
 * Recognises auto-replies (out-of-office) and bounces so they are never threaded as
 * a real customer reply. Checks the common standard headers and a few subject
 * tells — deliberately conservative; a borderline message is treated as a real reply.
 */
class AutoReplyDetector
{
    public function isAutomated(InboundEmail $email): bool
    {
        // RFC 3834 / common provider headers.
        $autoSubmitted = strtolower((string) $email->header('auto-submitted'));
        if ($autoSubmitted !== '' && $autoSubmitted !== 'no') {
            return true;
        }

        if ($email->header('x-autoreply') !== null
            || $email->header('x-autorespond') !== null
            || $email->header('x-failed-recipients') !== null) {
            return true;
        }

        $precedence = strtolower((string) $email->header('precedence'));
        if (in_array($precedence, ['auto_reply', 'bulk', 'junk'], true)) {
            return true;
        }

        // Bounce envelope sender.
        $from = strtolower((string) $email->header('from'));
        if (str_contains($from, 'mailer-daemon') || str_contains($from, 'postmaster@')) {
            return true;
        }

        $subject = strtolower((string) $email->header('subject'));

        foreach (['out of office', 'auto-reply', 'automatic reply', 'autoreply', 'undeliverable', 'delivery status notification'] as $tell) {
            if (str_contains($subject, $tell)) {
                return true;
            }
        }

        return false;
    }
}
