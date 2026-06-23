<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * The Horizon gate has two paths: a bearer token (for the Ploi panel, which
 * calls with no logged-in user) and the existing "any authenticated user"
 * rule. These must not weaken each other.
 */
class HorizonGateTest extends TestCase
{
    private function presentBearer(?string $token): void
    {
        $request = Request::create('/horizon', 'GET');

        if ($token !== null) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        // request() inside the gate resolves this instance.
        $this->app->instance('request', $request);
    }

    public function test_matching_bearer_token_authorises_without_a_user(): void
    {
        config(['services.horizon.token' => 'a-real-token']);
        $this->presentBearer('a-real-token');

        $this->assertTrue(Gate::allows('viewHorizon'));
    }

    public function test_wrong_token_falls_back_to_the_user_rule(): void
    {
        config(['services.horizon.token' => 'a-real-token']);
        $this->presentBearer('not-the-token');

        // No user → denied (token branch failed, fallback needs a user).
        $this->assertFalse(Gate::allows('viewHorizon'));

        // Authenticated admin → still allowed via the unchanged existing rule.
        $this->assertTrue(Gate::forUser(User::factory()->create())->allows('viewHorizon'));
    }

    public function test_empty_token_config_can_never_be_satisfied_by_a_bearer(): void
    {
        config(['services.horizon.token' => null]);
        $this->presentBearer(''); // empty bearer must not match an empty config

        $this->assertFalse(Gate::allows('viewHorizon'));

        // The user fallback is unaffected.
        $this->assertTrue(Gate::forUser(User::factory()->create())->allows('viewHorizon'));
    }

    public function test_no_token_and_no_user_is_denied(): void
    {
        config(['services.horizon.token' => null]);
        $this->presentBearer(null);

        $this->assertFalse(Gate::allows('viewHorizon'));
    }
}
