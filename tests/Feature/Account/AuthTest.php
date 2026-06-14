<?php

namespace Tests\Feature\Account;

use App\Mail\AccountLoginLinkMail;
use App\Models\Customer;
use App\Models\CustomerLoginLink;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_requesting_a_link_emails_a_known_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'jess@example.com']);

        $this->post('/account/login', ['email' => 'jess@example.com'])
            ->assertRedirect()
            ->assertSessionHas('account_status');

        Mail::assertQueued(AccountLoginLinkMail::class, fn (AccountLoginLinkMail $m): bool => $m->hasTo('jess@example.com'));
        $this->assertSame(1, $customer->loginLinks()->count());
    }

    public function test_unknown_email_gets_the_same_neutral_response_and_no_email(): void
    {
        $this->post('/account/login', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('account_status');

        Mail::assertNothingQueued();
        $this->assertSame(0, CustomerLoginLink::count());
    }

    public function test_a_valid_link_logs_the_customer_in_and_is_single_use(): void
    {
        $customer = Customer::factory()->create();
        $token = CustomerLoginLink::issueFor($customer);

        $this->get('/account/login/'.$token)->assertRedirect('/account');
        $this->assertTrue(auth('customer')->check());
        $this->assertSame($customer->id, auth('customer')->id());

        // Token consumed → a second use fails and does not log in afresh.
        auth('customer')->logout();
        $this->get('/account/login/'.$token)->assertRedirect('/account/login');
        $this->assertFalse(auth('customer')->check());
    }

    public function test_an_expired_link_does_not_log_in(): void
    {
        $customer = Customer::factory()->create();
        $token = CustomerLoginLink::issueFor($customer, ttlMinutes: 20);
        CustomerLoginLink::query()->update(['expires_at' => now()->subMinute()]);

        $this->get('/account/login/'.$token)->assertRedirect('/account/login');
        $this->assertFalse(auth('customer')->check());
    }

    public function test_a_bogus_token_does_not_log_in(): void
    {
        $this->get('/account/login/'.str_repeat('x', 48))->assertRedirect('/account/login');
        $this->assertFalse(auth('customer')->check());
    }

    public function test_link_requests_are_rate_limited_per_email(): void
    {
        Customer::factory()->create(['email' => 'spam@example.com']);

        for ($i = 0; $i < 7; $i++) {
            $this->post('/account/login', ['email' => 'spam@example.com']);
        }

        // The controller cap is 5 issued links per email/IP window.
        Mail::assertQueued(AccountLoginLinkMail::class, 5);
    }

    public function test_account_requires_authentication(): void
    {
        $this->get('/account')->assertRedirect('/account/login');
    }

    public function test_logged_in_customer_can_view_the_dashboard(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/account')
            ->assertOk()
            ->assertSee('Your next jump')
            ->assertSee('My Account'); // header link shows when signed in
    }

    public function test_a_customer_cannot_reach_the_admin_panel(): void
    {
        $customer = Customer::factory()->create();

        // Authenticated on the customer guard only — never the admin web guard.
        $this->actingAs($customer, 'customer')
            ->get('/admin')
            ->assertRedirect();

        $this->assertFalse(auth('web')->check());
    }

    public function test_logout_ends_the_session(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')->post('/account/logout')->assertRedirect('/');
        $this->assertFalse(auth('customer')->check());
    }

    public function test_login_page_renders(): void
    {
        // The guest URL itself is reachable and not behind auth.
        $this->assertTrue(URL::route('account.login') !== '');
        $this->get('/account/login')->assertOk()->assertSee('Sign in');
    }
}
