<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * CLAUDE.md architecture rule 10: non-scrolling shells are sized in `svh`, never
 * `100vh` / `h-screen`. On mobile, 100vh is the toolbar-hidden height, so a shell
 * that never scrolls keeps anything pinned to its bottom off-screen — and headless
 * browsers, having no toolbar, can't see it. Viewport units are allowed only as
 * min-/max- caps (`min-h-screen`, `max-h-screen`, `min-height: 100vh`).
 */
class NoFullViewportHeightShellsTest extends TestCase
{
    public function test_no_view_or_stylesheet_sizes_a_shell_with_100vh_or_h_screen(): void
    {
        $files = [
            ...SourceFiles::under('resources/views'),
            ...SourceFiles::under('resources/css', '.css'),
        ];
        $this->assertNotEmpty($files, 'Found no views or stylesheets — the scan itself is broken.');

        $violations = [];
        foreach ($files as $path) {
            foreach (file($path) ?: [] as $number => $line) {
                foreach ($this->violationsIn($line) as $found) {
                    $violations[] = SourceFiles::relative($path).':'.($number + 1)." uses {$found}";
                }
            }
        }

        $this->assertSame([], $violations, "Size shells in svh; viewport units only as min-/max- caps:\n".implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private function violationsIn(string $line): array
    {
        $found = [];

        // `h-screen` not preceded by `min-` / `max-` (a responsive `lg:h-screen` still counts).
        if (preg_match('/(?<![\w-])h-screen\b/', $line)) {
            $found[] = 'h-screen';
        }

        // `100vh` anywhere (CSS, `h-[100vh]`, inline style) unless it's a min/max cap.
        preg_match_all('/100vh/', $line, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [, $offset]) {
            $before = rtrim(substr($line, 0, $offset));
            if (! preg_match('/(min|max)-(h|height)[\s:\-\[]*$/', $before)) {
                $found[] = '100vh';
            }
        }

        return $found;
    }
}
