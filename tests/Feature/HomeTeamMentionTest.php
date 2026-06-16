<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeTeamMentionTest extends TestCase
{
    public function test_about_band_links_to_team_as_an_explore_style_arrow_link(): void
    {
        Instructor::factory()->create();

        $html = $this->get('/')->assertOk()->getContent();

        // An EXPLORE-style arrow-link (white text + blue border-b underline accent)
        // routing to the team page — no teaser label line above it.
        $this->assertStringContainsString('href="/meet-the-team"', $html);
        $this->assertStringContainsString('Meet the Team', $html);
        // The "THE PEOPLE YOU'LL FLY WITH." teaser label was dropped.
        $this->assertStringNotContainsString('The people you', $html);

        // The duplicate About stat tiles are gone (their unique label no longer
        // appears); the trust credentials now live only in the Trust band.
        $this->assertStringNotContainsString('Highest UK Tandem', $html);
        // The Trust band itself is untouched.
        $this->assertStringContainsString('Trusted. Certified. Experienced.', $html);
    }

    public function test_about_team_link_is_gated_on_instructors_existing(): void
    {
        // The sitewide nav links the team page regardless; only the About link is
        // gated. Compare the meet-the-team link count with/without instructors —
        // exactly one extra link (the About one) appears when instructors exist.
        // Cache::flush() between states busts the cached SiteContent instructors.
        Instructor::query()->delete();
        Cache::flush();
        $navOnly = substr_count($this->get('/')->assertOk()->getContent(), 'href="/meet-the-team"');

        Instructor::factory()->create();
        Cache::flush();
        $withTeam = substr_count($this->get('/')->assertOk()->getContent(), 'href="/meet-the-team"');

        $this->assertSame($navOnly + 1, $withTeam);
    }
}
