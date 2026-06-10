<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Services\StripeCheckout;
use InvalidArgumentException;

class StartVoucherCheckout
{
    public function __construct(private readonly StripeCheckout $stripe) {}

    /**
     * Take full payment for a gift voucher. The voucher itself is only
     * generated when the webhook confirms payment — the purchase intent
     * (recipient + message) travels on the payment's metadata.
     *
     * @param  array{
     *     purchaser_name: string, purchaser_email: string,
     *     recipient_name: string, message?: string|null,
     * }  $data
     * @return array{payment: Payment, checkout_url: string}
     */
    public function handle(array $data): array
    {
        $product = Product::active()->ofType(ProductType::Tandem)->ordered()->first();

        if ($product === null || $product->price_pence === null) {
            throw new InvalidArgumentException('Gift vouchers are unavailable right now.');
        }

        Customer::resolve($data['purchaser_email'], $data['purchaser_name']);

        $payment = Payment::create([
            'purpose' => PaymentPurpose::VoucherPurchase,
            'method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount_pence' => $product->price_pence,
            'description' => 'Gift voucher — '.$product->name,
            'metadata' => [
                'product_id' => $product->id,
                'purchaser_name' => $data['purchaser_name'],
                'purchaser_email' => $data['purchaser_email'],
                'recipient_name' => $data['recipient_name'],
                'message' => $data['message'] ?? '',
            ],
        ]);

        try {
            $session = $this->stripe->createSession(
                $payment,
                expiresAfterMinutes: 30,
                cancelUrl: route('payment.cancelled', ['flow' => 'voucher']),
            );
        } catch (\Throwable $e) {
            $payment->update(['status' => PaymentStatus::Failed]);

            throw $e;
        }

        $payment->update(['stripe_checkout_session_id' => $session['id']]);

        return ['payment' => $payment, 'checkout_url' => $session['url']];
    }
}
