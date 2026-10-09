<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * CLAUDE.md architecture rule 8: nothing loads or inserts DOM inside a
 * Livewire-morphed view except Livewire. A module script inside a morphed
 * fragment arrives after the events it needed and never re-executes; an Alpine
 * x-if clone survives the morph and a second one is inserted.
 *
 * Scope: every view under resources/views/livewire/ AND every anonymous Blade
 * component those views render (transitively) — a component's markup is morphed
 * just the same as the view that includes it.
 */
class NoDomInsertionInLivewireViewsTest extends TestCase
{
    private const FORBIDDEN = [
        '@vite' => '/@vite\b/',
        '<script' => '/<script\b/i',
        'x-if' => '/\sx-if\s*=/',
    ];

    public function test_livewire_views_and_their_components_load_no_scripts_and_use_no_x_if(): void
    {
        $views = $this->livewireViewsAndComponents();
        $this->assertGreaterThanOrEqual(8, count($views), 'Expected at least the 8 Livewire views — the scan itself is broken.');

        $violations = [];
        foreach ($views as $path) {
            $lines = file($path) ?: [];
            foreach ($lines as $number => $line) {
                foreach (self::FORBIDDEN as $label => $pattern) {
                    if (preg_match($pattern, $line)) {
                        $violations[] = SourceFiles::relative($path).':'.($number + 1)." uses {$label}";
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Livewire-morphed markup must not load scripts or use x-if (load scripts from the layout; toggle with x-show):\n".implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private function livewireViewsAndComponents(): array
    {
        $views = SourceFiles::under('resources/views/livewire');
        $seen = [];
        $queue = $views;

        while ($queue !== []) {
            $path = array_shift($queue);
            if (isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;

            preg_match_all('/<x-([a-z0-9][a-z0-9.\-]*)/i', (string) file_get_contents($path), $matches);
            foreach (array_unique($matches[1]) as $component) {
                $file = SourceFiles::componentView($component);
                if ($file !== null && ! isset($seen[$file])) {
                    $queue[] = $file;
                }
            }
        }

        return array_keys($seen);
    }
}
