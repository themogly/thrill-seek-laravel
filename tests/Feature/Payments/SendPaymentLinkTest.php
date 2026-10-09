<?php

namespace Tests\Feature\Payments;

use App\Actions\SendPaymentLink;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Mail\TemplatedMail;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SendPaymentLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')
                ->andReturn(['id' => 'cs_test_abc123', 'url' => 'https://checkout.stripe.test/cs_test_abc123']);
        });
    }

    public function test_sending_a_payment_link_creates_a_pending_payment_and_emails_the_customer(): void
    {
        $enquiry = Enquiry::factory()->create();
        $user = User::factory()->create();

        $payment = app(SendPaymentLink::class)->handle(
            $enquiry,
            PaymentPurpose::TandemFull,
            26000,
            'Tandem Skydive',
            $user,
        );

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('cs_test_abc123', $payment->stripe_checkout_session_id);
        $this->assertSame(26000, $payment->amount_pence);
        $this->assertSame(EnquiryStatus::PaymentSent, $enquiry->refresh()->status);

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($enquiry->email)
            && str_contains($mail->renderedBody, 'https://checkout.stripe.test/cs_test_abc123')
            && str_contains($mail->renderedBody, '£260'));
    }

    public function test_admin_can_send_a_payment_link_from_the_enquiry_page(): void
    {
        $this->actingAs(User::factory()->create());
        $enquiry = Enquiry::factory()->create();

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('sendPaymentLink', [
                'purpose' => PaymentPurpose::Custom->value,
                'amount_pence' => 90, // pounds
                'description' => 'Coaching session',
            ])
            ->assertHasNoActionErrors();

        $payment = Payment::sole();
        $this->assertSame(9000, $payment->amount_pence);
        $this->assertSame(PaymentPurpose::Custom, $payment->purpose);

        // The test's name says the link is sent: the customer gets it, for £90.00.
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail): bool => $mail->hasTo($enquiry->email)
            && str_contains($mail->renderedBody, '£90'));
    }

    public function test_stripe_failures_surface_a_notification_instead_of_crashing(): void
    {
        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')->andThrow(new \RuntimeException('No API key provided.'));
        });

        $this->actingAs(User::factory()->create());
        $enquiry = Enquiry::factory()->create();

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('sendPaymentLink', [
                'purpose' => PaymentPurpose::Custom->value,
                'amount_pence' => 90, // pounds
                'description' => 'Coaching session',
            ])
            ->assertNotified();

        $this->assertSame(EnquiryStatus::New, $enquiry->refresh()->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
