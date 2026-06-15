<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Tests\TestCase;

class HomeTeamMentionTest extends TestCase
{
    public function test_about_band_carries_a_subtle_team_mention_and_link(): void
    {
        Instructor::factory()->create();

        $html = $this->get('/')->assertOk()->getContent();

        // The woven trust line + the single directional cue to the team page.
        $this->assertStringContainsString('British Skydiving and USPA-rated instructors', $html);
        $this->assertStringContainsString("Meet the instructors who'll fly with you", $html);
        $this->assertStringContainsString('href="/meet-the-team"', $html);

        // No heavy standalone teaser: no bordered box of nameless avatars, no rail.
        $this->assertStringNotContainsString('ring-2 ring-background', $html);
        $this->assertStringNotContainsString('overflow-x-auto', $html);
    }

    public function test_mention_is_absent_when_there_are_no_instructors(): void
    {
        Instructor::query()->delete();

        // The Why Us dropdown still links the team page sitewide; only the woven
        // About mention (trust line + "meet the instructors" cue) drops out.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('British Skydiving and USPA-rated instructors', $html);
        $this->assertStringNotContainsString("Meet the instructors who'll fly with you", $html);
    }
}
