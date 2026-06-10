<?php

namespace Tests\Feature;

use App\Livewire\NewsletterSignup;
use App\Models\NewsletterSubscriber;
use Livewire\Livewire;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    public function test_a_visitor_can_subscribe_from_the_banner(): void
    {
        Livewire::test(NewsletterSignup::class, ['variant' => 'banner'])
            ->set('email', 'Fan@Example.com')
            ->call('subscribe')
            ->assertDispatched('enquiry-sent');

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'fan@example.com']);
    }

    public function test_duplicate_subscriptions_are_idempotent_and_still_friendly(): void
    {
        NewsletterSubscriber::subscribe('fan@example.com');

        Livewire::test(NewsletterSignup::class)
            ->set('email', 'fan@example.com')
            ->call('subscribe')
            ->assertDispatched('enquiry-sent');

        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_invalid_emails_are_rejected_with_a_toast(): void
    {
        Livewire::test(NewsletterSignup::class)
            ->set('email', 'not-an-email')
            ->call('subscribe')
            ->assertDispatched('enquiry-failed')
            ->assertHasErrors('email');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_honeypot_blocks_bots(): void
    {
        Livewire::test(NewsletterSignup::class)
            ->set('email', 'bot@example.com')
            ->set('website', 'spam')
            ->call('subscribe');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_the_forms_render_on_home_and_contact(): void
    {
        $this->get('/')->assertOk()->assertSeeLivewire(NewsletterSignup::class);
        $this->get('/contact')->assertOk()->assertSeeLivewire(NewsletterSignup::class);
    }
}
