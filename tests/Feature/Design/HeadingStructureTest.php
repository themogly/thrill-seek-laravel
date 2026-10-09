<?php

namespace Tests\Feature\Design;

use App\Models\NewsArticle;
use App\Models\Testimonial;
use DOMDocument;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

use function Livewire\trigger;

/**
 * Screen-reader users move through a page by its headings. Every public page has
 * exactly one <h1> and never skips a level going down (h1 → h3). Rendered with a
 * featured testimonial and a published article so the conditional heroes and
 * card grids are on the page.
 */
class HeadingStructureTest extends TestCase
{
    public function test_every_public_page_has_one_h1_and_no_skipped_heading_levels(): void
    {
        Testimonial::factory()->create(['featured' => true, 'approved' => true]);
        Testimonial::factory()->count(2)->create(['approved' => true]);
        $article = NewsArticle::factory()->create(['published' => true, 'published_at' => now()->subDay()]);

        $uris = [...$this->publicPageUris(), '/news/'.$article->slug];
        $problems = [];
        $checked = 0;

        foreach ($uris as $uri) {
            trigger('flush-state');
            $response = $this->get($uri);
            if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                continue;
            }
            $checked++;

            $levels = $this->headingLevels((string) $response->getContent());
            $h1s = count(array_filter($levels, fn (int $level): bool => $level === 1));
            if ($h1s !== 1) {
                $problems[] = "{$uri}: {$h1s} <h1> elements";
            }

            $previous = 0;
            foreach ($levels as $level) {
                if ($level > $previous + 1) {
                    $problems[] = "{$uri}: <h{$previous}> is followed by <h{$level}>";
                }
                $previous = $level;
            }
        }

        $this->assertGreaterThanOrEqual(10, $checked, 'Rendered fewer than 10 pages — the route walk is broken.');
        $this->assertSame([], $problems, "Heading structure:\n".implode("\n", $problems));
    }

    /**
     * @return list<int>
     */
    private function headingLevels(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);

        $levels = [];
        foreach ($dom->getElementsByTagName('*') as $node) {
            if (preg_match('/^h([1-6])$/i', $node->nodeName, $m)) {
                $levels[] = (int) $m[1];
            }
        }

        return $levels;
    }

    /**
     * @return list<string>
     */
    private function publicPageUris(): array
    {
        $skip = ['admin', 'livewire', 'webhooks', 'dev', 'horizon', 'up', 'account', 'filament', 'storage', 'sitemap.xml', 'robots.txt', 'feed'];

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
