<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\CustomerLoginLink;
use Tests\TestCase;

/**
 * The request-level half of CLAUDE.md architecture rule 9 (false-green.md #6).
 *
 * The test client normally shares one in-memory session store and one auth guard
 * across requests, so a later request "sees" state that was never round-tripped
 * through a cookie. Here the state is set by a real request, everything in-process
 * is thrown away, and the second request carries ONLY the session cookie — so the
 * session-reading `auth:customer` middleware on /account sees the login only if the
 * session genuinely persisted and loaded before it ran.
 */
class SessionSurvivesARealRequestTest extends TestCase
{
    public function test_a_login_set_by_one_request_is_read_by_the_next_through_the_cookie_alone(): void
    {
        // A persistent driver: the default `array` store lives in process memory.
        config(['session.driver' => 'database']);

        $customer = Customer::factory()->create();
        $token = CustomerLoginLink::issueFor($customer);

        $login = $this->get('/account/login/'.$token)->assertRedirect('/account');
        $sessionId = $login->getCookie(config('session.cookie'))?->getValue();
        $this->assertNotEmpty($sessionId, 'The login response set no session cookie.');

        // Drop every in-process carrier of the login: the session store, its
        // drivers and the resolved guards.
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['auth']->forgetGuards();
        $this->assertFalse(auth('customer')->check(), 'In-process auth state survived the reset.');

        $this->withCookie(config('session.cookie'), $sessionId)
            ->get('/account')
            ->assertOk();
    }
}
