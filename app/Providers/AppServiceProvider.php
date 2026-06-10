<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
