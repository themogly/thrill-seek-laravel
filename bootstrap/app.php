<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/resend',
        ]);

        // Defence-in-depth response headers (incl. a report-only CSP) on every
        // web response — public site and admin panel alike.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'feature' => EnsureFeatureEnabled::class,
        ]);

        // Account-area guests go to the customer sign-in; everything else
        // (the admin panel) goes to the Filament login.
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('account', 'account/*')
            ? route('account.login')
            : '/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
