<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebhookThrottleTest extends TestCase
{
    private function postRoute(string $uri): ?RouteObject
    {
        return collect(Route::getRoutes()->getRoutes())
            ->first(fn (RouteObject $r): bool => $r->uri() === ltrim($uri, '/') && in_array('POST', $r->methods(), true));
    }

    public function test_both_webhook_routes_carry_a_throttle(): void
    {
        foreach (['/webhooks/stripe', '/webhooks/resend'] as $uri) {
            $route = $this->postRoute($uri);
            $this->assertNotNull($route, "Missing POST route for {$uri}");
            $this->assertContains('throttle:120,1', $route->gatherMiddleware(), "No throttle on {$uri}");
        }
    }

    public function test_normal_volume_is_not_throttled(): void
    {
        // Calls with no/invalid signature are rejected by the controller (4xx),
        // never throttled (429) — real delivery + retry volume passes untouched.
        foreach (['/webhooks/stripe', '/webhooks/resend'] as $uri) {
            for ($i = 0; $i < 10; $i++) {
                $status = $this->postJson($uri, ['ping' => $i])->getStatusCode();
                $this->assertNotSame(429, $status, "{$uri} throttled at normal volume");
            }
        }
    }

    public function test_a_flood_beyond_the_limit_is_rejected(): void
    {
        $statuses = [];
        for ($i = 0; $i < 130; $i++) {
            $statuses[] = $this->postJson('/webhooks/stripe', ['ping' => $i])->getStatusCode();
        }

        $this->assertContains(429, $statuses, 'Expected the throttle to return 429 within 130 calls.');
    }
}
