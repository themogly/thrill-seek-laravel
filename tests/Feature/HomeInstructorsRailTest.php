<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Tests\TestCase;

class HomeInstructorsRailTest extends TestCase
{
    public function test_rail_renders_one_card_per_instructor_for_any_count(): void
    {
        // The layout is CMS-count-driven and must hold for 1, 3 or 5+ coaches.
        foreach ([1, 3, 5] as $count) {
            Instructor::query()->delete();
            Instructor::factory()->count($count)->create();

            $html = $this->get('/')->assertOk()->getContent();

            $this->assertSame($count, substr_count($html, 'role="listitem"'), "Expected {$count} coach cards.");
            // 2-up on mobile, 4-up on desktop; flex-1 lets few cards fill the row
            // and many overflow into a horizontal scroll rather than stacking.
            $this->assertStringContainsString('min-w-[50%]', $html);
            $this->assertStringContainsString('lg:min-w-[25%]', $html);
            $this->assertStringContainsString('overflow-x-auto', $html);
        }
    }
}
