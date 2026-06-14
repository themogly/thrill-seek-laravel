<?php

namespace App\Support\Inbound;

/**
 * Reduce a raw inbound email to just the new reply, stripping quoted history and
 * signatures. A line-by-line heuristic (not a full RFC parser): we cut at the first
 * recognised quote header, ">" quote block, provider divider or signature marker.
 * The caller keeps the full original in `raw_body`, so an over-aggressive cut is
 * always recoverable. Tuned for the common Gmail / Apple Mail / Outlook shapes.
 */
class EmailReplyParser
{
    /** @var list<string> regexes that mark the start of quoted/old content */
    private const CUT_MARKERS = [
        '/^On .+wrote:$/u',                 // Gmail / Apple "On <date>, <name> wrote:"
        '/^-{2,}\s*Original Message\s*-{2,}/iu',
        '/^_{5,}$/u',                        // Outlook horizontal divider
        '/^From:\s.+/u',                     // Outlook quoted header block
        '/^Sent from my /u',                 // mobile signature
        '/^Get Outlook for /iu',
    ];

    public function parse(string $raw): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $kept = [];

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);

            // A line of ">" quoted text — but only treat it as the cut point once
            // we've already kept some reply (a mail that opens with ">" is all quote).
            if (str_starts_with($trimmed, '>')) {
                break;
            }

            // Signature delimiter ("-- " on its own line).
            if ($trimmed === '--' || $line === '-- ') {
                break;
            }

            foreach (self::CUT_MARKERS as $marker) {
                if (preg_match($marker, $trimmed) === 1) {
                    break 2;
                }
            }

            // A wrapped "On <date>\n<name> wrote:" — peek for "wrote:" close.
            if (str_starts_with($trimmed, 'On ') && ! str_contains($trimmed, 'wrote:')
                && isset($lines[$i + 1]) && str_ends_with(trim($lines[$i + 1]), 'wrote:')) {
                break;
            }

            $kept[] = $line;
        }

        return trim(implode("\n", $kept));
    }
}
