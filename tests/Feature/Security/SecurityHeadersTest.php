<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_baseline_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('geolocation=()', $response->headers->get('Permissions-Policy') ?? '');
    }

    public function test_csp_is_report_only_and_allows_the_stack_we_depend_on(): void
    {
        $response = $this->get('/');

        // Report-only so it cannot break Livewire/Alpine/Stripe before it's verified.
        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $csp = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("'unsafe-eval'", $csp);          // Alpine
        $this->assertStringContainsString('checkout.stripe.com', $csp);    // Stripe redirect
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_hsts_is_not_sent_outside_production_https(): void
    {
        // Local test env is not production, so HSTS must be absent.
        $response = $this->get('/');

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_is_sent_in_production_over_https(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        $response = $this->get('https://thrill-seek.test/');

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security'),
        );
    }
}
