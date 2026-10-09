<?php

namespace Tests\Feature\Mail;

use App\Support\BrandHex;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Prompt 023: emails and PDFs never use the old bright blue (#2f8de4, 3.47:1 on
 * white) as text or as a fill under text on a light surface. Every use of it,
 * literal or through BrandHex::ACCENT, is read with the CSS property it sits in:
 * borders are decoration and always fine; `color` / `background` uses must be on
 * the allowlist, each with its reason. The fix moved the rest to
 * BrandHex::STRONG (#0078cc, the site's primary-strong).
 */
class EmailColourTest extends TestCase
{
    /** file :: the line's distinctive text => why the bright blue is right there. */
    private const ALLOWED = [
        'resources/views/mail/newsletter/footer.blade.php :: >Instagram<' => 'Text on the navy band (#0a0f23): ACCENT is 5.47:1 there, STRONG only 4.12:1.',
        'resources/views/mail/newsletter/footer.blade.php :: >Facebook<' => 'Text on the navy band: as above.',
        'resources/views/mail/blocks/featured_course.blade.php :: Featured AFF course' => 'Eyebrow on the navy block: as above.',
        'resources/views/pdf/voucher.blade.php :: .amount {' => 'Large display figure (34px bold): 3:1 applies and ACCENT is 3.47:1, the site\'s ≥24px rule (Ben, 9 Oct).',
    ];

    private const DIRS = ['resources/views/mail', 'resources/views/components/mail', 'resources/views/vendor/mail', 'resources/views/pdf'];

    public function test_the_old_blue_is_never_text_or_a_fill_under_text_on_a_light_surface(): void
    {
        $this->assertSame([], $this->violations($this->uses()));
    }

    public function test_the_check_catches_a_planted_text_use(): void
    {
        $planted = [...$this->uses(), ['resources/views/mail/blocks/planted.blade.php', 'color', '<a style="color:#2f8de4">Planted</a>']];

        $this->assertSame(['resources/views/mail/blocks/planted.blade.php: color in <a style="color:#2f8de4">Planted</a>'], $this->violations($planted));
    }

    public function test_the_walk_reaches_the_known_uses(): void
    {
        $properties = array_column($this->uses(), 1);

        // Borders (voucher band, code box, message rule, theme panel) and the four allowlisted text uses.
        $this->assertGreaterThanOrEqual(4, count(array_filter($properties, fn (string $p): bool => str_starts_with($p, 'border'))));
        $this->assertSame(4, count(array_filter($properties, fn (string $p): bool => $p === 'color')));
    }

    public function test_the_strong_blue_passes_under_white_and_the_accent_passes_on_navy(): void
    {
        $this->assertGreaterThanOrEqual(4.5, $this->ratio(BrandHex::STRONG, '#ffffff'));
        $this->assertGreaterThanOrEqual(4.5, $this->ratio(BrandHex::ACCENT, BrandHex::NAVY));
        $this->assertLessThan(4.5, $this->ratio(BrandHex::ACCENT, '#ffffff'), 'If the accent ever passes on white, this allowlist can relax.');
    }

    public function test_mail_buttons_are_the_strong_blue_under_pure_white_text(): void
    {
        $partial = (string) file_get_contents(resource_path('views/mail/blocks/button.blade.php'));
        $this->assertStringContainsString('background-color:{{ \App\Support\BrandHex::STRONG }}', $partial);
        $this->assertStringContainsString('color:#ffffff', $partial);

        // The static Markdown-mail theme can't call PHP, so it is pinned to the constant here.
        $theme = (string) file_get_contents(resource_path('views/vendor/mail/html/themes/gforce.css'));
        $this->assertMatchesRegularExpression('/\.button-primary \{\s*\/\*[^*]*\*\/\s*background-color: '.BrandHex::STRONG.';/', $theme);
        $this->assertMatchesRegularExpression('/\.button \{[^}]*color: #fff;/', $theme);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $uses
     * @return list<string>
     */
    private function violations(array $uses): array
    {
        $violations = [];

        foreach ($uses as [$file, $property, $line]) {
            if (str_starts_with($property, 'border') || $this->allowed($file, $line)) {
                continue;
            }

            $violations[] = "{$file}: {$property} in ".trim($line);
        }

        return $violations;
    }

    private function allowed(string $file, string $line): bool
    {
        foreach (array_keys(self::ALLOWED) as $key) {
            [$allowedFile, $snippet] = explode(' :: ', $key, 2);

            if ($allowedFile === $file && str_contains($line, $snippet)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array{0: string, 1: string, 2: string}> [file, CSS property, line] */
    private function uses(): array
    {
        $uses = [];
        $blue = '(?:#2f8de4|\{\{\s*\\\\?App\\\\Support\\\\BrandHex::ACCENT\s*\}\})';

        foreach (Finder::create()->files()->in(array_map(base_path(...), self::DIRS))->sortByName() as $file) {
            $relative = str_replace(base_path().'/', '', $file->getPathname());

            foreach (explode("\n", $file->getContents()) as $line) {
                $code = preg_replace('#/\*.*?\*/|\{\{--.*?--\}\}#', '', $line);

                preg_match_all('/([a-z-]+)\s*:\s*[^;"{}]*?'.$blue.'/i', (string) $code, $matches);

                foreach ($matches[1] as $property) {
                    $uses[] = [$relative, strtolower($property), $line];
                }
            }
        }

        return $uses;
    }

    private function ratio(string $a, string $b): float
    {
        $lum = function (string $hex): float {
            $c = array_map(fn (string $x): float => hexdec($x) / 255, str_split(ltrim($hex, '#'), 2));
            $lin = array_map(fn (float $v): float => $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

            return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
        };

        [$x, $y] = [$lum($a), $lum($b)];

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }
}
