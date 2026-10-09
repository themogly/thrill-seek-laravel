<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Live prices inside CMS wording (prompt 014, consistency audit C-7).
 *
 * The owner types `{price:tandem-skydive}`, `{deposit:aff-course}` or
 * `{addon:outside-camera}` into a supported field and it renders as that
 * product's / add-on's CURRENT price through Money — so changing a price on the
 * product changes every sentence that quotes it (one reader per figure).
 *
 * - `price:` / `deposit:` take a product's "Reference (slug)"; `addon:` takes the
 *   add-on's name, slugified ("P6 Third Party Insurance" → p6-third-party-insurance).
 * - Single braces, not `{{ }}`, so they can never be mistaken for the email
 *   templates' `{{ name }}` placeholders or Blade.
 * - Resolved at render time from SiteContent::priceTokens() — a cached map of
 *   plain integers, never models.
 * - Unknown tokens NEVER render raw in public: they become PUBLIC_FALLBACK (the
 *   wording the site already uses for an unpriced product) and are logged. The
 *   admin preview flags them visibly instead (see AdminPriceTokens).
 *
 * Detection is deliberately loose (`{Price: tandem }`, a mistyped slug…) so a
 * near-miss is still caught rather than leaking braces onto the page.
 */
final class PriceTokens
{
    private const PATTERN = '/\{\s*(price|deposit|addon)\s*:\s*([^{}]*?)\s*\}/i';

    public const PUBLIC_FALLBACK = 'price on enquiry';

    public function __construct(private readonly SiteContent $content) {}

    /** Public render: every token becomes a formatted price, or the fallback. */
    public function render(string $text): string
    {
        if (! $this->hasTokens($text)) {
            return $text;
        }

        $known = $this->content->priceTokens();

        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($known): string {
            $key = self::key($match[1], $match[2]);
            if (array_key_exists($key, $known)) {
                return Money::formatPence($known[$key]);
            }

            Log::warning('Unknown price token in site copy — rendered as the fallback.', ['token' => $match[0]]);

            return self::PUBLIC_FALLBACK;
        }, $text);
    }

    /**
     * Admin preview (escaped HTML, tags stripped): known tokens show their price,
     * unknown ones are highlighted so the owner sees them before the public does.
     */
    public function preview(string $text): string
    {
        $known = $this->content->priceTokens();
        $plain = e(trim((string) preg_replace('/\s+/', ' ', strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', $text)))));

        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($known): string {
            $key = self::key($match[1], $match[2]);

            return array_key_exists($key, $known)
                ? '<strong>'.e(Money::formatPence($known[$key])).'</strong>'
                : '<mark><strong>⚠ Unknown price '.$match[0].'</strong></mark>';
        }, $plain);
    }

    /** @return list<string> the tokens in $text that don't resolve, as typed */
    public function unknown(string $text): array
    {
        if (! $this->hasTokens($text)) {
            return [];
        }

        $known = $this->content->priceTokens();
        preg_match_all(self::PATTERN, $text, $matches, PREG_SET_ORDER);

        return array_values(array_unique(array_map(
            fn (array $match): string => $match[0],
            array_filter($matches, fn (array $match): bool => ! array_key_exists(self::key($match[1], $match[2]), $known)),
        )));
    }

    public function hasTokens(string $text): bool
    {
        return preg_match(self::PATTERN, $text) === 1;
    }

    /** @return array<string, string> every usable token (as typed) => its current formatted price */
    public function available(): array
    {
        $tokens = [];
        foreach ($this->content->priceTokens() as $key => $pence) {
            $tokens['{'.$key.'}'] = Money::formatPence($pence);
        }

        return $tokens;
    }

    private static function key(string $kind, string $reference): string
    {
        return strtolower($kind).':'.strtolower(trim($reference));
    }
}
