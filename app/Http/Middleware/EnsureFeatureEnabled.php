<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind a public feature toggle (Settings → General → Features).
 * A disabled feature 404s — the page genuinely does not exist while it is off,
 * and no underlying code or data is removed. Apply as `feature:shop`.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $settings = app(GeneralSettings::class);

        $enabled = match ($feature) {
            'shop' => $settings->shop_enabled,
            default => abort(500, "Unknown feature toggle: {$feature}"),
        };

        abort_unless($enabled, 404);

        return $next($request);
    }
}
