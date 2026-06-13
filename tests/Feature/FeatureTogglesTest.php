<?php

namespace Tests\Feature;

use Tests\TestCase;

class FeatureTogglesTest extends TestCase
{
    public function test_shop_is_hidden_and_404s_when_disabled(): void
    {
        // Off is the default.
        $this->get('/shop')->assertNotFound();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertDontSee('href="/shop"', false);
    }

    public function test_shop_is_visible_and_reachable_when_enabled(): void
    {
        $this->setFeature('shop_enabled', true);

        $this->get('/shop')->assertOk();

        $home = $this->get('/');
        $home->assertSee('href="/shop"', false);
    }

    public function test_sitemap_includes_shop_only_when_enabled(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>/shop</loc>', false);

        $this->setFeature('shop_enabled', true);

        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>/shop</loc>', false);
    }
}
