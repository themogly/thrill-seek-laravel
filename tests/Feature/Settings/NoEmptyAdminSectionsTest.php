<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;
use Tests\Unit\Architecture\SourceFiles;

/**
 * Removing a field must not leave its admin Section behind as an empty box
 * (012 left an empty "Meet the team" teaser section on the Home settings page).
 * Field-less Builder blocks (divider, automatic news) are deliberate and not checked.
 */
class NoEmptyAdminSectionsTest extends TestCase
{
    public function test_no_admin_section_is_left_empty(): void
    {
        $empty = [];
        foreach (SourceFiles::under('app/Filament', '.php') as $path) {
            $source = (string) file_get_contents($path);
            preg_match_all('/Section::make\(/', $source, $sections, PREG_OFFSET_CAPTURE);
            foreach ($sections[0] as [, $offset]) {
                // The section's own children are the first ->components([ / ->schema([ after it.
                if (preg_match('/->(?:components|schema)\(\[(\s*)(\]?)/', $source, $m, 0, $offset) && $m[2] === ']') {
                    $empty[] = SourceFiles::relative($path).':'.(substr_count($source, "\n", 0, $offset) + 1);
                }
            }
        }

        $this->assertSame([], $empty, "Empty admin sections — remove them with their last field:\n".implode("\n", $empty));
    }
}
