<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class RobotsTest extends TestCase
{
    public function test_thin_payment_pages_are_noindex(): void
    {
        $this->get('/payment/cancelled')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow" />', false);
    }

    public function test_indexable_pages_have_no_robots_noindex(): void
    {
        $this->get('/tandem')
            ->assertOk()
            ->assertDontSee('noindex', false);
    }

    public function test_robots_txt_references_the_absolute_sitemap_and_disallows_admin(): void
    {
        // The production file; every other environment disallows everything (NoindexNonProductionTest).
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
            ->assertSee('Disallow: /admin', false);
    }
}
