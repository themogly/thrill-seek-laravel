<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Tests\TestCase;

class HomeTeamMentionTest extends TestCase
{
    public function test_about_band_shows_a_team_arrow_link_and_no_duplicate_stats(): void
    {
        Instructor::factory()->create();

        $html = $this->get('/')->assertOk()->getContent();

        // A quiet label + animated arrow-link (modelled on the service-card EXPLORE
        // cue), routing to the team page — not a second dominant heading/button.
        $this->assertStringContainsString('href="/meet-the-team"', $html);
        $this->assertStringContainsString('Meet the Team', $html);
        $this->assertStringContainsString('The people you', $html);

        // The duplicate About stat tiles are gone (their unique label no longer
        // appears); the trust credentials now live only in the Trust band.
        $this->assertStringNotContainsString('Highest UK Tandem', $html);
        // The Trust band itself is untouched.
        $this->assertStringContainsString('Trusted. Certified. Experienced.', $html);
    }

    public function test_team_button_is_absent_when_there_are_no_instructors(): void
    {
        Instructor::query()->delete();

        // The Why Us dropdown still links the team page sitewide; only the About
        // team button + lead drop out.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('The people you', $html);
    }
}
