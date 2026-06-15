<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Tests\TestCase;

class HomeTeamTeaserTest extends TestCase
{
    public function test_teaser_shows_an_avatar_per_instructor_and_links_to_the_team_page(): void
    {
        // The teaser is CMS-count-driven and must hold for 1, 3 or 5+ coaches
        // without ever becoming a scroll rail.
        foreach ([1, 3, 5] as $count) {
            Instructor::query()->delete();
            Instructor::factory()->count($count)->create(['photo' => null]);

            $html = $this->get('/')->assertOk()->getContent();

            // One avatar (initial fallback) per instructor.
            $this->assertSame($count, substr_count($html, 'ring-2 ring-background'), "Expected {$count} avatars.");
            // Links through to the full team page.
            $this->assertStringContainsString('href="/meet-the-team"', $html);
            // No horizontal scroll rail and no per-instructor bios on the homepage.
            $this->assertStringNotContainsString('overflow-x-auto', $html);
        }
    }

    public function test_teaser_renders_the_editable_trust_line(): void
    {
        Instructor::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('British Skydiving and USPA-rated instructors');
    }

    public function test_teaser_is_absent_when_there_are_no_instructors(): void
    {
        Instructor::query()->delete();

        // The Why Us dropdown still links the team page sitewide; only the
        // homepage teaser block (avatars + trust line) drops out.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('ring-2 ring-background', $html);
        $this->assertStringNotContainsString('British Skydiving and USPA-rated instructors', $html);
    }
}
