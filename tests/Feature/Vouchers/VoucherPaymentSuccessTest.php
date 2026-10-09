<?php

namespace Tests\Feature\Vouchers;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use Tests\TestCase;

/**
 * After buying a gift voucher, the success page confirms the purchase — not
 * "waiting to confirm your payment" forever. It's resolved from the Stripe
 * session id (unguessable), exactly like the booking path, so a visitor only
 * ever sees the voucher their own checkout bought.
 */
class VoucherPaymentSuccessTest extends TestCase
{
    private function voucherPurchase(string $session, PaymentStatus $status, string $recipient): Payment
    {
        $product = Product::factory()->tandem()->create(['name' => 'Tandem Skydive']);
        $payment = Payment::factory()->create([
            'purpose' => PaymentPurpose::VoucherPurchase,
            'status' => $status,
            'amount_pence' => 26000,
            'stripe_checkout_session_id' => $session,
            'metadata' => ['product_id' => $product->id, 'purchaser_name' => 'Gifter', 'purchaser_email' => 'gifter@example.test', 'recipient_name' => $recipient],
        ]);

        if ($status === PaymentStatus::Paid) {
            Voucher::factory()->create([
                'payment_id' => $payment->id,
                'product_id' => $product->id,
                'amount_pence' => 26000,
                'purchaser_email' => 'gifter@example.test',
                'recipient_name' => $recipient,
                'source' => 'online',
            ]);
        }

        return $payment;
    }

    public function test_a_paid_voucher_purchase_shows_the_voucher_confirmation(): void
    {
        $this->voucherPurchase('cs_voucher_paid', PaymentStatus::Paid, 'Lucky Grandkid');

        $this->get('/payment/success?session_id=cs_voucher_paid')
            ->assertOk()
            ->assertSee('Gift voucher')
            ->assertSee('Tandem Skydive')
            ->assertSee('£260')
            ->assertSee('Lucky Grandkid')
            ->assertSee('gifter@example.test')
            ->assertDontSee('waiting to confirm');
    }

    public function test_an_unconfirmed_voucher_payment_still_shows_the_waiting_state(): void
    {
        $this->voucherPurchase('cs_voucher_pending', PaymentStatus::Pending, 'Someone');

        $this->get('/payment/success?session_id=cs_voucher_pending')
            ->assertOk()
            ->assertSee('waiting to confirm');
    }

    public function test_another_customers_voucher_is_never_shown(): void
    {
        $this->voucherPurchase('cs_mine', PaymentStatus::Paid, 'My Recipient');
        $this->voucherPurchase('cs_theirs', PaymentStatus::Paid, 'Their Recipient');

        $this->get('/payment/success?session_id=cs_mine')
            ->assertSee('My Recipient')
            ->assertDontSee('Their Recipient');

        $this->get('/payment/success?session_id=cs_unknown')
            ->assertOk()
            ->assertDontSee('My Recipient')
            ->assertDontSee('Their Recipient');
    }
}
