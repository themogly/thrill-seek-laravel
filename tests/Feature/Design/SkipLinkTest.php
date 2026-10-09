<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

/**
 * Keyboard users can jump past the header navigation: the first focusable
 * element on every page is a "Skip to content" link to the <main> landmark.
 */
class SkipLinkTest extends TestCase
{
    public function test_every_page_starts_with_a_skip_link_to_main(): void
    {
        foreach (['/', '/tandem', '/contact'] as $uri) {
            $html = (string) $this->get($uri)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<body[^>]*>\s*<a href="#main"[^>]*>Skip to content<\/a>/', $html, "{$uri} has no skip link first in <body>.");
            $this->assertMatchesRegularExpression('/<main[^>]*id="main"/', $html);
        }
    }
}
