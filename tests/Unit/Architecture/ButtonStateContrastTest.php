<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Prompt 020: every <x-ui.button> variant's hover and active (press) colours,
 * resolved from the real tokens in app.css and the real classes in the
 * component, keep its text at WCAG AA (4.5:1). An opacity wash on a fill
 * (`bg-primary-strong/85`) is composited over the surface the way the browser
 * paints it, so a wash that lightens the fill toward the page fails here.
 */
class ButtonStateContrastTest extends TestCase
{
    private const AA = 4.5;

    /** Light and dark surfaces an outline/link button sits on: [text token, surface token]. */
    private const LIGHT = ['foreground', 'background'];

    private const DARK = [['secondary-foreground', 'secondary'], ['secondary-foreground', 'ink'], ['secondary-foreground', 'sky-deep']];

    public function test_every_variant_keeps_aa_on_hover_and_press(): void
    {
        $this->assertSame([], $this->failures($this->variants()));
    }

    public function test_the_check_catches_a_lightening_wash(): void
    {
        $planted = ['primary' => 'bg-primary-strong text-primary-foreground hover:bg-primary-strong/85 active:bg-primary-strong/75'];

        $failures = $this->failures($planted);

        $this->assertCount(2, $failures);
        $this->assertStringContainsString('primary hover', $failures[0]);
    }

    public function test_the_colour_maths_matches_the_browser(): void
    {
        // primary-strong = #0078cc, measured by axe at 4.61:1 with white (016).
        $this->assertSame('#0078cc', $this->hex($this->token('primary-strong')));
        $this->assertEqualsWithDelta(4.6, $this->ratio($this->token('primary-strong'), $this->token('background')), 0.05);
    }

    /**
     * @param  array<string, string>  $variants  name => class list
     * @return list<string>
     */
    private function failures(array $variants): array
    {
        $failures = [];

        foreach ($variants as $name => $classes) {
            foreach (['hover', 'active'] as $state) {
                foreach ($this->pairs($name, $classes, $state) as [$label, $text, $fill]) {
                    $ratio = $this->ratio($text, $fill);

                    if ($ratio < self::AA) {
                        $failures[] = sprintf('%s %s %s: %.2f:1', $name, $state, $label, $ratio);
                    }
                }
            }
        }

        return $failures;
    }

    /** @return list<array{0: string, 1: list<float>, 2: list<float>}> [label, text rgb, fill rgb] */
    private function pairs(string $variant, string $classes, string $state): array
    {
        $fill = $this->stateClass($classes, $state, 'bg');
        $text = $this->stateClass($classes, $state, 'text');

        if ($variant === 'primary') {
            $under = $this->token('background');
            $fillRgb = $fill === null ? $this->restFill($classes) : $this->resolve($fill, $under);

            return [['on its own fill', $this->resolve($this->restClass($classes, 'text'), $under), $fillRgb]];
        }

        // outline and link inherit or set their text colour and sit on a light or dark surface.
        $surfaces = [self::LIGHT, ...($variant === 'outline' ? self::DARK : [])];
        $pairs = [];

        foreach ($surfaces as [$textToken, $surfaceToken]) {
            $surface = $this->token($surfaceToken);
            $textRgb = $text !== null
                ? $this->resolve($text, $surface)
                : ($this->restClass($classes, 'text') !== null ? $this->resolve($this->restClass($classes, 'text'), $surface) : $this->token($textToken));
            $fillRgb = $fill === null ? $surface : $this->resolve($fill, $surface, $textRgb);
            $pairs[] = ["on {$surfaceToken}", $textRgb, $fillRgb];
        }

        return $pairs;
    }

    /** The colour part of e.g. `hover:bg-primary-strong-hover` → `primary-strong-hover`. */
    private function stateClass(string $classes, string $state, string $kind): ?string
    {
        return preg_match('/(?<![\w-])'.$state.':'.$kind.'-([a-z-]+(?:\/\d+)?)(?![\w-])/', $classes, $m) === 1 ? $m[1] : null;
    }

    private function restClass(string $classes, string $kind): ?string
    {
        return preg_match('/(?<![\w:-])'.$kind.'-((?:primary|secondary|foreground|background|ink|sky)[a-z-]*(?:\/\d+)?)(?![\w-])/', $classes, $m) === 1 ? $m[1] : null;
    }

    /** @return list<float> */
    private function restFill(string $classes): array
    {
        return $this->resolve((string) $this->restClass($classes, 'bg'), $this->token('background'));
    }

    /**
     * `token` or `token/NN`, composited over $under; `current/NN` uses the text colour.
     *
     * @param  list<float>  $under
     * @param  list<float>|null  $current
     * @return list<float>
     */
    private function resolve(string $colour, array $under, ?array $current = null): array
    {
        [$name, $alpha] = str_contains($colour, '/') ? explode('/', $colour) : [$colour, '100'];
        $rgb = $name === 'current' ? (array) $current : $this->token($name);
        $a = ((int) $alpha) / 100;

        return array_map(fn (float $t, float $u): float => $t * $a + $u * (1 - $a), $rgb, $under);
    }

    /** @return array<string, string> */
    private function variants(): array
    {
        $blade = (string) file_get_contents(__DIR__.'/../../../resources/views/components/ui/button.blade.php');
        preg_match_all("/'(primary|outline|link)' => '([^']+)'/", $blade, $m, PREG_SET_ORDER);

        $variants = [];
        foreach ($m as [, $name, $classes]) {
            $variants[$name] = $classes;
        }

        $this->assertSame(['primary', 'outline', 'link'], array_keys($variants), 'The button lost a variant this test reads.');

        return $variants;
    }

    /** @return list<float> gamma-encoded sRGB 0–1 */
    private function token(string $name): array
    {
        $css = (string) file_get_contents(__DIR__.'/../../../resources/css/app.css');

        $this->assertMatchesRegularExpression('/--'.preg_quote($name, '/').':\s*oklch\(/', $css, "No --{$name} oklch token.");
        preg_match('/--'.preg_quote($name, '/').':\s*oklch\(([\d.]+)\s+([\d.]+)\s+([\d.]+)\)/', $css, $m);

        return $this->oklchToSrgb((float) $m[1], (float) $m[2], (float) $m[3]);
    }

    /** @return list<float> */
    private function oklchToSrgb(float $l, float $c, float $h): array
    {
        $a = $c * cos(deg2rad($h));
        $b = $c * sin(deg2rad($h));
        $l_ = ($l + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m_ = ($l - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s_ = ($l - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        $linear = [
            4.0767416621 * $l_ - 3.3077115913 * $m_ + 0.2309699292 * $s_,
            -1.2684380046 * $l_ + 2.6097574011 * $m_ - 0.3413193965 * $s_,
            -0.0041960863 * $l_ - 0.7034186147 * $m_ + 1.7076147010 * $s_,
        ];

        return array_map(
            fn (float $v): float => max(0.0, min(1.0, $v <= 0.0031308 ? 12.92 * $v : 1.055 * ($v ** (1 / 2.4)) - 0.055)),
            $linear,
        );
    }

    /**
     * @param  list<float>  $x
     * @param  list<float>  $y
     */
    private function ratio(array $x, array $y): float
    {
        $lx = $this->luminance($x);
        $ly = $this->luminance($y);

        return (max($lx, $ly) + 0.05) / (min($lx, $ly) + 0.05);
    }

    /** @param  list<float>  $rgb */
    private function luminance(array $rgb): float
    {
        $lin = array_map(fn (float $v): float => $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $rgb);

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    /** @param  list<float>  $rgb */
    private function hex(array $rgb): string
    {
        return '#'.implode('', array_map(fn (float $v): string => sprintf('%02x', (int) round($v * 255)), $rgb));
    }
}
