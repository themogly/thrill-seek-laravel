<?php

namespace Tests\Feature\Design;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

use function Livewire\trigger;

/**
 * The page-level half of the Alpine scope guard (false-green.md #7): correct
 * directives inside an x-data root are still dead markup if the page ships no
 * Alpine. G-Force gets Alpine inside Livewire's bundle, which the one public
 * layout loads with @livewireScripts — so this renders every parameterless public
 * GET page and asserts that any page with `x-data` carries the Livewire script.
 */
class AlpineShipsWherePagesUseItTest extends TestCase
{
    public function test_every_public_page_rendering_x_data_loads_alpine(): void
    {
        $checked = 0;
        $withAlpine = 0;

        foreach ($this->publicPageUris() as $uri) {
            // Livewire emits its script once per process and remembers it did; a real
            // FPM request starts fresh, so reset that in-process flag per page.
            trigger('flush-state');
            $response = $this->get($uri);
            if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                continue;
            }

            $checked++;
            $html = (string) $response->getContent();
            if (! str_contains($html, 'x-data')) {
                continue;
            }

            $withAlpine++;
            $this->assertMatchesRegularExpression(
                '#<script[^>]+src="[^"]*/livewire[^"]*/livewire(\.min)?\.js#',
                $html,
                "{$uri} renders x-data but loads no Livewire/Alpine script — its Alpine is dead markup.",
            );
        }

        $this->assertGreaterThanOrEqual(10, $checked, 'Rendered fewer than 10 public pages — the route walk is broken.');
        $this->assertGreaterThan(0, $withAlpine, 'No page rendered x-data — the check proved nothing.');
    }

    /**
     * @return list<string>
     */
    private function publicPageUris(): array
    {
        $skip = ['admin', 'livewire', 'webhooks', 'dev', 'horizon', 'up', 'account', 'filament', 'storage', 'sitemap', 'robots', 'feed'];

        return collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => in_array('GET', $route->methods(), true)
                && ! str_contains($route->uri(), '{')
                && ! in_array(explode('/', $route->uri())[0], $skip, true)
                && ! str_starts_with($route->uri(), 'livewire-'))
            ->map(fn (Route $route): string => '/'.ltrim($route->uri(), '/'))
            ->unique()
            ->values()
            ->all();
    }
}
