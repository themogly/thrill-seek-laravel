<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Prompt 024 guard: a table that scrolls sideways goes through the ONE shared
 * region, `<x-ui.table-scroll label="…">` (focusable, named, focus ring), never a
 * bare `overflow-x-auto` wrapper a keyboard can't reach. Mail and PDF views are
 * out of scope (not interactive).
 */
class TablesScrollThroughTheSharedRegionTest extends TestCase
{
    private const SKIP = ['resources/views/mail/', 'resources/views/vendor/mail/', 'resources/views/pdf/', 'resources/views/components/mail/'];

    public function test_no_table_sits_in_a_bare_scroll_wrapper(): void
    {
        $violations = [];

        foreach (SourceFiles::under('resources/views') as $file) {
            $relative = substr($file, strlen(SourceFiles::root()) + 1);

            if (array_filter(self::SKIP, fn (string $skip): bool => str_starts_with($relative, $skip)) !== []) {
                continue;
            }

            foreach ($this->violations((string) file_get_contents($file)) as $line) {
                $violations[] = "{$relative}:{$line}";
            }
        }

        $this->assertSame([], $violations, 'Wrap the table in <x-ui.table-scroll label="…"> instead.');
    }

    public function test_the_guard_catches_a_planted_bare_wrapper(): void
    {
        $planted = "<section>\n    <div class=\"mt-8 overflow-x-auto border-2\">\n        <table class=\"w-full\"></table>\n    </div>\n</section>";

        $this->assertSame([2], $this->violations($planted));
    }

    public function test_the_shared_region_passes(): void
    {
        $this->assertSame([], $this->violations("<x-ui.table-scroll label=\"Payments\" class=\"mt-8\">\n    <table></table>\n</x-ui.table-scroll>"));
    }

    /** @return list<int> 1-based lines of bare scroll wrappers whose next element is a table */
    private function violations(string $source): array
    {
        preg_match_all('/<div\b[^>]*class="[^"]*\boverflow(?:-x)?-(?:auto|scroll)\b[^"]*"[^>]*>\s*<table\b/', $source, $matches, PREG_OFFSET_CAPTURE);

        return array_map(fn (array $match): int => substr_count(substr($source, 0, $match[1]), "\n") + 1, $matches[0]);
    }
}
