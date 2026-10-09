<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

/**
 * Prompt 022 (Ben, 9 Oct 2026: do it in the app): only production may be
 * indexed. Staging and any preview host disallow everything in /robots.txt and
 * send X-Robots-Tag on every response; production output is unchanged.
 */
class NoindexNonProductionTest extends TestCase
{
    private const PRODUCTION_ROBOTS = "User-agent: *\nDisallow: /admin\nDisallow: /admin/\nDisallow: /dev/\n\nSitemap: http://localhost/sitemap.xml\n";

    public function test_staging_robots_disallows_everything_with_no_sitemap(): void
    {
        $this->asEnvironment('staging');

        $response = $this->get('/robots.txt')->assertOk();

        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_staging_sends_noindex_on_html_pages(): void
    {
        $this->asEnvironment('staging');

        foreach (['/', '/tandem', '/contact', '/account/login', '/sitemap.xml'] as $path) {
            $this->get($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_any_non_production_host_is_noindexed(): void
    {
        foreach (['local', 'testing', 'preview'] as $environment) {
            $this->asEnvironment($environment);

            $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_production_robots_is_todays_file_exactly(): void
    {
        $this->asEnvironment('production');

        $response = $this->get('/robots.txt')->assertOk();

        $this->assertSame(self::PRODUCTION_ROBOTS, $response->getContent());
        $response->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_production_pages_carry_no_noindex(): void
    {
        $this->asEnvironment('production');

        foreach (['/', '/tandem', '/contact'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertHeaderMissing('X-Robots-Tag')
                ->assertDontSee('noindex', false);
        }
    }

    private function asEnvironment(string $environment): void
    {
        $this->app->detectEnvironment(fn (): string => $environment);
    }
}
