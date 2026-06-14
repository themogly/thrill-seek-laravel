<?php

namespace App\Providers;

use App\Contracts\InboundEmailFetcher;
use App\Services\Newsletter\NativeNewsletterService;
use App\Services\Newsletter\NewsletterService;
use App\Support\Inbound\ResendInboundEmailFetcher;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap this binding to move list management to another provider.
        $this->app->bind(NewsletterService::class, NativeNewsletterService::class);

        // Second-step inbound fetch (webhook → full body); faked in tests.
        $this->app->bind(InboundEmailFetcher::class, ResendInboundEmailFetcher::class);

        $this->app->bind(StripeClient::class, function (): StripeClient {
            $secret = config('services.stripe.secret');

            // An empty string throws in the StripeClient constructor, which
            // would 500 any request that merely injects it. A null api_key
            // (array form) constructs fine and throws AuthenticationException
            // at call time, where the booking flows catch it and show a
            // friendly message.
            return new StripeClient([
                'api_key' => is_string($secret) && $secret !== '' ? $secret : null,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
