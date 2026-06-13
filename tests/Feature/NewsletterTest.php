<?php

namespace Tests\Feature;

use App\Enums\NewsletterStatus;
use App\Livewire\NewsletterSignup;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_subscribing_creates_a_pending_record_and_emails_a_confirmation(): void
    {
        Livewire::test(NewsletterSignup::class, ['variant' => 'banner'])
            ->set('email', 'Fan@Example.com')
            ->call('subscribe')
            ->assertDispatched('enquiry-sent');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('fan@example.com', $subscriber->email);
        $this->assertSame(NewsletterStatus::Pending, $subscriber->status);
        $this->assertNotNull($subscriber->consented_at, 'Consent timestamp recorded at signup.');
        $this->assertNull($subscriber->confirmed_at);

        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_confirmed_subscribers_are_not_duplicated_or_re_emailed(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'fan@example.com']);

        Livewire::test(NewsletterSignup::class)
            ->set('email', 'fan@example.com')
            ->call('subscribe')
            ->assertDispatched('enquiry-sent');

        $this->assertSame(1, NewsletterSubscriber::count());
        Mail::assertNothingQueued();
    }

    public function test_confirming_via_signed_link_marks_the_subscriber_confirmed(): void
    {
        $subscriber = NewsletterSubscriber::factory()->pending()->create();

        $this->get(URL::signedRoute('newsletter.confirm', ['subscriber' => $subscriber->id]))
            ->assertOk()
            ->assertSee("You're in!");

        $subscriber->refresh();
        $this->assertSame(NewsletterStatus::Confirmed, $subscriber->status);
        $this->assertNotNull($subscriber->confirmed_at);
    }

    public function test_unsubscribing_via_signed_link_is_immediate_and_recorded(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $this->get(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]))
            ->assertOk()
            ->assertSee('Unsubscribed');

        $subscriber->refresh();
        $this->assertSame(NewsletterStatus::Unsubscribed, $subscriber->status);
        $this->assertNotNull($subscriber->unsubscribed_at);
    }

    public function test_an_unsigned_confirm_link_is_rejected(): void
    {
        $subscriber = NewsletterSubscriber::factory()->pending()->create();

        $this->get("/newsletter/confirm/{$subscriber->id}")->assertForbidden();

        $this->assertSame(NewsletterStatus::Pending, $subscriber->refresh()->status);
    }

    public function test_an_unsubscribed_address_can_resubscribe_and_returns_to_pending(): void
    {
        $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'back@example.com']);

        app(NewsletterService::class)->subscribe('back@example.com', 'footer');

        $subscriber->refresh();
        $this->assertSame(NewsletterStatus::Pending, $subscriber->status);
        $this->assertNull($subscriber->unsubscribed_at);
        Mail::assertQueued(NewsletterConfirmationMail::class);
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

    public function test_the_forms_render_on_home_contact_and_the_dedicated_page(): void
    {
        $this->get('/')->assertOk()->assertSeeLivewire(NewsletterSignup::class);
        $this->get('/contact')->assertOk()->assertSeeLivewire(NewsletterSignup::class);
        $this->get('/newsletter')->assertOk()->assertSeeLivewire(NewsletterSignup::class);
    }
}
