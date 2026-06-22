<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * Two authorisation paths:
     *  - A bearer token matching HORIZON_TOKEN (used by the Ploi panel, which
     *    calls the Horizon endpoints with no logged-in user). Constant-time
     *    compared, and only honoured when a token is actually configured — an
     *    empty HORIZON_TOKEN can never authorise.
     *  - Otherwise the existing rule, unchanged: every account in the users
     *    table is an admin (no public accounts), so any authenticated user
     *    may view Horizon.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            $configured = (string) config('services.horizon.token');
            $presented = (string) request()->bearerToken();

            if ($configured !== '' && $presented !== '' && hash_equals($configured, $presented)) {
                return true;
            }

            return $user !== null;
        });
    }
}
