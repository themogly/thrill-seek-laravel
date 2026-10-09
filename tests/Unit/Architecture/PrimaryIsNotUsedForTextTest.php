<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Brand contrast, option B (prompt 016): `primary` (#008fe6, 3.46:1 on white) is
 * the decorative accent; anything READ — small text, and fills under text — uses
 * `primary-strong` (4.6:1). This guard fails on a `text-primary` or a
 * `bg-primary`-under-text in any Blade view, unless the use is one of the
 * decorative categories below or on the explicit allowlist (each with its reason).
 */
class PrimaryIsNotUsedForTextTest extends TestCase
{
    /**
     * Uses that are not body text, by file and a snippet unique to the use.
     *
     * @var array<string, array<string, string>>
     */
    private const ALLOWLIST = [
        'components/site/section-heading.blade.php' => [
            "\$light ? 'text-primary'" => 'The eyebrow on a DARK band (light=true): primary is 4.2:1 on navy, primary-strong only 3.2:1.',
        ],
        'pages/contact.blade.php' => [
            'class="hover:text-primary"' => 'Hover colour of links on the NAVY contact panel: primary is 4.2:1 there; primary-strong would drop it to 3.2:1.',
        ],
    ];

    /** Large display sizes (>= 24px): WCAG large text needs 3:1, and primary measures 3.46:1. */
    private const LARGE_DISPLAY = ['text-2xl', 'text-3xl', 'text-4xl', 'text-5xl', 'text-6xl', 'text-7xl', 'text-8xl', 'text-[10rem]'];

    public function test_primary_is_only_used_for_decoration_in_views(): void
    {
        $violations = [];
        foreach (SourceFiles::under('resources/views') as $path) {
            $relative = substr(SourceFiles::relative($path), strlen('resources/views/'));
            foreach (self::violations((string) file_get_contents($path), $relative) as $violation) {
                $violations[] = $violation;
            }
        }

        $this->assertSame([], $violations, "primary used for text — use primary-strong (or add a reasoned ALLOWLIST entry):\n".implode("\n", $violations));
    }

    public function test_the_guard_catches_a_planted_violation(): void
    {
        $planted = <<<'BLADE'
            <p class="text-sm font-bold text-primary">Small blue text</p>
            <a href="/x" class="bg-primary px-4 text-primary-foreground">Read me</a>
            <a href="/y" class="text-primary-strong hover:text-primary">Hover drifts back</a>
            BLADE;

        $this->assertCount(3, self::violations($planted, 'planted.blade.php'));
    }

    public function test_the_guard_allows_decoration(): void
    {
        $decoration = <<<'BLADE'
            <x-icon name="check" class="h-4 w-4 text-primary" />
            <svg class="h-5 w-5 text-primary"></svg>
            <input type="checkbox" class="h-5 w-5 text-primary" />
            <span class="inline-block h-0.5 w-10 bg-primary"></span>
            <p class="font-display text-3xl text-primary">£260</p>
            <span class="flex h-12 w-12 bg-primary text-primary-foreground"><x-icon name="check" class="h-7 w-7" /></span>
            <p class="text-sm text-primary-strong">Readable</p>
            <div class="border-t-4 border-primary bg-primary/10 text-secondary">Tint</div>
            BLADE;

        $this->assertSame([], self::violations($decoration, 'decoration.blade.php'));
    }

    /** @return list<string> */
    private static function violations(string $source, string $relative): array
    {
        $found = [];
        // Every class attribute (static or Alpine/Blade-bound) and the tag it sits in.
        preg_match_all('/<([a-z][\w.:-]*)\b[^>]*?\bclass="([^"]*)"[^>]*>/is', $source, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($tags as $tag) {
            [$whole, $offset] = $tag[0];
            $name = strtolower($tag[1][0]);
            $classes = $tag[2][0];
            $line = substr_count($source, "\n", 0, $offset) + 1;
            $after = ltrim(substr($source, $offset + strlen($whole), 300));

            $isIcon = in_array($name, ['x-icon', 'svg'], true) || str_starts_with($after, '<x-icon') && ! preg_match('/^<x-icon[^>]*\/>\s*[^<\s]/', $after);
            $isCheckbox = str_contains($whole, 'type="checkbox"');
            $isLargeDisplay = str_contains($classes, 'font-display') && array_filter(self::LARGE_DISPLAY, fn (string $size): bool => preg_match('/(^|\s)'.preg_quote($size, '/').'(\s|$)/', $classes) === 1) !== [];

            $textPrimary = preg_match('/(^|[\s\'"{])([\w&\[\]:\/-]*:)?text-primary(\/\d+)?(?![\w-])/', $classes) === 1;
            $fillUnderText = preg_match('/(^|[\s\'"{])([\w&\[\]:\/-]*:)?bg-primary(\/\d{2,})?(?![\w\/-])/', $classes) === 1
                && preg_match('/text-(primary-foreground|white)/', $classes) === 1;

            if (! $textPrimary && ! $fillUnderText) {
                continue;
            }
            if ($isIcon || $isCheckbox || ($textPrimary && ! $fillUnderText && $isLargeDisplay)) {
                continue;
            }
            foreach (self::ALLOWLIST[$relative] ?? [] as $snippet => $reason) {
                if (str_contains($whole, $snippet)) {
                    continue 2;
                }
            }

            $found[] = "{$relative}:{$line} ".trim(preg_replace('/\s+/', ' ', substr($whole, 0, 140)));
        }

        return $found;
    }
}
