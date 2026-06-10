<?php

namespace Tests\Feature\Vouchers;

use App\Actions\RedeemVoucher;
use App\Enums\BookingStatus;
use App\Enums\PaymentPurpose;
use App\Enums\VoucherStatus;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Livewire\BookTandem;
use App\Livewire\BuyVoucher;
use App\Mail\VoucherGiftMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TandemDate;
use App\Models\User;
use App\Models\Voucher;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PublicVoucherPurchaseTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')
                ->andReturn(['id' => 'cs_voucher_1', 'url' => 'https://checkout.stripe.test/cs_voucher_1'])
                ->byDefault();
        });
    }

    public function test_the_voucher_page_renders_with_the_tandem_price(): void
    {
        // The page-hero splits title words into spans, so assert the
        // subtitle and the price panel instead of the full title phrase.
        $this->get('/vouchers')
            ->assertOk()
            ->assertSee('the gift nobody forgets', false)
            ->assertSee('£260');
    }

    public function test_buying_a_voucher_creates_a_pending_payment_with_the_gift_intent(): void
    {
        Livewire::test(BuyVoucher::class)
            ->set('purchaser_name', 'Generous Gran')
            ->set('purchaser_email', 'gran@example.com')
            ->set('recipient_name', 'Lucky Grandkid')
            ->set('message', 'Happy 21st!')
            ->set('terms', true)
            ->call('pay')
            ->assertRedirect('https://checkout.stripe.test/cs_voucher_1');

        $payment = Payment::sole();
        $this->assertSame(PaymentPurpose::VoucherPurchase, $payment->purpose);
        $this->assertSame(26000, $payment->amount_pence);
        $this->assertSame('Lucky Grandkid', $payment->metadata['recipient_name']);
        $this->assertSame(0, Voucher::count()); // not until the webhook
    }

    public function test_the_webhook_issues_the_voucher_and_sends_the_gift_email_once(): void
    {
        $payment = Payment::factory()->create([
            'purpose' => PaymentPurpose::VoucherPurchase,
            'amount_pence' => 26000,
            'stripe_checkout_session_id' => 'cs_voucher_2',
            'metadata' => [
                'product_id' => Product::where('slug', 'tandem-skydive')->value('id'),
                'purchaser_name' => 'Generous Gran',
                'purchaser_email' => 'gran@example.com',
                'recipient_name' => 'Lucky Grandkid',
                'message' => 'Happy 21st!',
            ],
        ]);

        $this->postWebhook('checkout.session.completed', 'cs_voucher_2')->assertNoContent();
        // Stripe retries must not issue a second voucher or email.
        $this->postWebhook('checkout.session.completed', 'cs_voucher_2')->assertNoContent();

        $voucher = Voucher::sole();
        $this->assertSame('online', $voucher->source);
        $this->assertSame($payment->id, $voucher->payment_id);
        $this->assertSame('Lucky Grandkid', $voucher->recipient_name);
        $this->assertSame(26000, $voucher->amount_pence);
        $this->assertTrue($voucher->isRedeemable());

        Mail::assertQueued(VoucherGiftMail::class, 1);
        Mail::assertQueued(VoucherGiftMail::class, fn (VoucherGiftMail $mail) => $mail->hasTo('gran@example.com'));
    }

    public function test_a_full_value_voucher_books_a_tandem_without_stripe(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 2]);
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);

        $component = $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->call('continueToReview')
            ->set('voucherCode', $voucher->code)
            ->call('applyVoucher')
            ->set('terms', true)
            ->call('pay');

        $booking = Booking::sole();
        $component->assertRedirect(route('payment.success', ['booking' => $booking->reference]));

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertFalse($booking->hasOutstandingBalance());
        $this->assertSame(VoucherStatus::Redeemed, $voucher->refresh()->status);
        $this->assertSame($booking->id, $voucher->booking_id);
        $this->assertSame(1, $slot->refresh()->activeBookingsCount());

        // The claim-check success page shows the booking with no session id.
        $this->get('/payment/success?booking='.$booking->reference)
            ->assertOk()
            ->assertSee($booking->reference);
    }

    public function test_a_partial_voucher_charges_the_remainder_and_redeems_on_webhook(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 2]);
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);
        $camera = Product::where('slug', 'tandem-skydive')->firstOrFail()
            ->addOns()->where('name', 'Outside Camera')->firstOrFail();

        $this->mock(StripeCheckout::class, function ($mock): void {
            $mock->shouldReceive('createSession')
                ->andReturn(['id' => 'cs_partial_1', 'url' => 'https://checkout.stripe.test/cs_partial_1']);
        });

        $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->set('addOnIds', [$camera->id])
            ->call('continueToReview')
            ->set('voucherCode', $voucher->code)
            ->call('applyVoucher')
            ->set('terms', true)
            ->call('pay')
            ->assertRedirect('https://checkout.stripe.test/cs_partial_1');

        // Only the £140 remainder is charged; the voucher is untouched until payment lands.
        $stripePayment = Payment::sole();
        $this->assertSame(14000, $stripePayment->amount_pence);
        $this->assertSame($voucher->id, $stripePayment->metadata['voucher_id']);
        $this->assertSame(VoucherStatus::Active, $voucher->refresh()->status);

        $this->postWebhook('checkout.session.completed', 'cs_partial_1');

        $booking = Booking::sole()->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(VoucherStatus::Redeemed, $voucher->refresh()->status);
        $this->assertFalse($booking->hasOutstandingBalance()); // 26000 voucher + 14000 card = 40000 price
    }

    public function test_used_and_expired_codes_are_rejected_in_the_flow(): void
    {
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2), 'capacity' => 2]);
        $used = Voucher::factory()->create(['status' => VoucherStatus::Redeemed]);
        $expired = Voucher::factory()->expired()->create();

        $component = $this->fillDetails(Livewire::test(BookTandem::class)->call('chooseSlot', $slot->id))
            ->call('continueToReview');

        $component->set('voucherCode', $used->code)->call('applyVoucher');
        $this->assertStringContainsString('redeemed', $component->get('voucherMessage'));
        $this->assertNull($component->get('appliedVoucherId'));

        $component->set('voucherCode', $expired->code)->call('applyVoucher');
        $this->assertStringContainsString('expired', $component->get('voucherMessage'));

        $component->set('voucherCode', 'GV-NOPE1234')->call('applyVoucher');
        $this->assertStringContainsString("don't recognise", $component->get('voucherMessage'));
    }

    public function test_concurrent_redemption_cannot_double_spend_a_voucher(): void
    {
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);
        $bookingA = Booking::factory()->create(['price_pence' => 26000]);
        $bookingB = Booking::factory()->create(['price_pence' => 26000]);

        app(RedeemVoucher::class)->handle($voucher, $bookingA, User::factory()->create());

        // The second redemption re-reads a stale "active" voucher instance —
        // the atomic claim must still reject it.
        $stale = Voucher::find($voucher->id);
        $stale->status = VoucherStatus::Active;

        $this->expectException(InvalidArgumentException::class);
        app(RedeemVoucher::class)->handle($stale, $bookingB);
    }

    public function test_admin_can_revoke_an_active_voucher(): void
    {
        $this->actingAs(User::factory()->create());
        $voucher = Voucher::factory()->create();

        Livewire::test(ListVouchers::class)
            ->callTableAction('revoke', $voucher);

        $this->assertSame(VoucherStatus::Cancelled, $voucher->refresh()->status);
        $this->assertFalse($voucher->isRedeemable());
    }

    /**
     * @param  Testable  $component
     */
    private function fillDetails($component)
    {
        return $component
            ->set('name', 'Lucky Grandkid')
            ->set('email', 'kid@example.com')
            ->set('phone', '07700900222')
            ->set('date_of_birth', now()->subYears(21)->format('Y-m-d'))
            ->set('weight_kg', '70')
            ->set('emergency_contact_name', 'Generous Gran')
            ->set('emergency_contact_phone', '07700900333');
    }

    private function postWebhook(string $type, string $sessionId): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_voucher_1',
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => ['id' => $sessionId, 'object' => 'checkout.session', 'payment_intent' => 'pi_v_1']],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', '/webhooks/stripe', content: $payload, server: [
            'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ]);
    }
}
