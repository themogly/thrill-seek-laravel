<?php

namespace Tests\Feature\Booking;

use App\Livewire\BookTandem;
use App\Models\TandemDate;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NewsletterOptInTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')
                ->andReturn(['id' => 'cs_optin', 'url' => 'https://checkout.stripe.test/cs_optin'])
                ->byDefault();
        });
    }

    public function test_ticking_the_box_subscribes_through_the_newsletter_backend(): void
    {
        $this->book(optIn: true, email: 'keen@example.com');

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'keen@example.com']);
    }

    public function test_leaving_the_box_unticked_does_not_subscribe(): void
    {
        $this->book(optIn: false, email: 'private@example.com');

        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'private@example.com']);
    }

    public function test_the_opt_in_defaults_to_unticked(): void
    {
        Livewire::test(BookTandem::class)->assertSet('newsletterOptIn', false);
    }

    private function book(bool $optIn, string $email): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2)->setTime(9, 0), 'capacity' => 4]);

        Livewire::test(BookTandem::class)
            ->call('chooseSlot', $slot->id)
            ->set('name', 'Jess Jumper')
            ->set('email', $email)
            ->set('phone', '07700900123')
            ->set('date_of_birth', now()->subYears(28)->format('Y-m-d'))
            ->set('weight_kg', '80')
            ->set('emergency_contact_name', 'Pat Carer')
            ->set('emergency_contact_phone', '07700900456')
            ->set('newsletterOptIn', $optIn)
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');
    }
}
