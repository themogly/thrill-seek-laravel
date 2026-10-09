<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defence-in-depth response headers on every web response.
 *
 * The Content-Security-Policy is shipped in **Report-Only** mode: browsers
 * report violations to the console but enforce nothing, so it cannot break
 * Livewire, Alpine, Motion One or the Stripe Checkout redirect. Once the console
 * is confirmed clean in production the header name can be switched to the
 * enforcing `Content-Security-Policy` — see DECISIONS.md (SEC-P3.1).
 *
 * The policy is intentionally generous on scripts/styles because Alpine needs
 * `unsafe-eval` (it compiles `x-*` expressions with the Function constructor) and
 * Livewire/Alpine inject inline `<script>`/`style` — everything else is locked to
 * `'self'`. All assets (JS, CSS, fonts) are self-hosted via Vite; the only
 * third party is Stripe, reached by a top-level redirect to its Checkout host.
 *
 * Only production may be indexed: every other environment (staging, a preview
 * host, local) sends `X-Robots-Tag: noindex, nofollow` on every web response, so
 * a forgotten basic-auth rule can't put a duplicate site in Google (prompt 022;
 * /robots.txt disallows everything there too). It sits on the `web` group with
 * the other headers; it reads no session, so its order doesn't matter.
 */
class SecurityHeaders
{
    /**
     * @phpstan-var array<string, string>
     */
    private const POLICY = [
        'default-src' => "'self'",
        'base-uri' => "'self'",
        'object-src' => "'none'",
        'frame-ancestors' => "'self'",
        'script-src' => "'self' 'unsafe-inline' 'unsafe-eval'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: blob: https:",
        'font-src' => "'self' data:",
        'connect-src' => "'self'",
        'frame-src' => "'self' https://js.stripe.com https://checkout.stripe.com",
        'form-action' => "'self' https://checkout.stripe.com",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Content-Security-Policy-Report-Only', $this->contentSecurityPolicy());

        if (! app()->isProduction()) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // HSTS only over HTTPS in production — never on local http (it would pin
        // the browser to https for a domain that has no certificate).
        if (app()->isProduction() && $request->secure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [];

        foreach (self::POLICY as $directive => $value) {
            $directives[] = $directive.' '.$value;
        }

        return implode('; ', $directives);
    }
}
