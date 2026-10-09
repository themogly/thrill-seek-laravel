<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\RequestPasswordReset;
use App\Models\Customer;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\ResetPassword as ResetPasswordPage;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prompt 027 (the admin audit's A-3): the owner can reset a forgotten admin
 * password by email, and the request page never reveals whether an address has
 * an account.
 */
class AdminPasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_login_offers_a_reset_and_the_request_page_renders(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('/admin/password-reset/request', false);
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_a_staff_email_queues_the_reset_link_to_that_user(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['email' => 'owner@example.com']);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'owner@example.com'])
            ->call('request')
            ->assertNotified(__('passwords.sent'));

        Notification::assertSentTo($owner, ResetPassword::class);
    }

    public function test_an_unknown_email_sends_nothing_and_gets_the_same_notice(): void
    {
        Notification::fake();
        Customer::factory()->create(['email' => 'customer@example.com']);

        foreach (['nobody@example.com', 'customer@example.com'] as $email) {
            Livewire::test(RequestPasswordReset::class)
                ->fillForm(['email' => $email])
                ->call('request')
                ->assertNotified(__('passwords.sent'));
        }

        Notification::assertNothingSent();
    }

    public function test_the_emailed_link_resets_the_password_and_the_new_one_logs_in(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['email' => 'owner@example.com', 'password' => Hash::make('forgotten-one')]);

        Livewire::test(RequestPasswordReset::class)->fillForm(['email' => 'owner@example.com'])->call('request');

        $url = null;
        Notification::assertSentTo($owner, ResetPassword::class, function (ResetPassword $notification) use (&$url): bool {
            $url = $notification->url;

            return true;
        });

        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

        Livewire::withQueryParams(['email' => $owner->email, 'token' => $query['token']])
            ->test(ResetPasswordPage::class)
            ->fillForm(['password' => 'a-brand-new-passphrase', 'passwordConfirmation' => 'a-brand-new-passphrase'])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('a-brand-new-passphrase', $owner->fresh()->password));
        $this->assertTrue(auth()->guard('web')->attempt(['email' => 'owner@example.com', 'password' => 'a-brand-new-passphrase']));
    }

    public function test_the_reset_email_renders_in_the_brand_theme(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $notification = new ResetPassword('a-token');
        $notification->url = Filament::getResetPasswordUrl('a-token', $owner);

        $html = (string) $notification->toMail($owner)->render();

        $this->assertStringContainsString('Reset Password', $html);
        $this->assertStringContainsString('password-reset/reset', $html);
        $this->assertStringContainsString('#0078cc', $html, 'The button uses the gforce theme (BrandHex::STRONG).');
    }
}
