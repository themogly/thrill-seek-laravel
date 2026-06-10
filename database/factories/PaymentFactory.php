<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'enquiry_id' => null,
            'booking_id' => null,
            'purpose' => PaymentPurpose::Custom,
            'method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount_pence' => fake()->numberBetween(5000, 200000),
            'description' => fake()->sentence(3),
            'reference' => null,
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->lexify('????????????'),
            'stripe_payment_intent_id' => null,
            'paid_at' => null,
            'created_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state([
            'method' => PaymentMethod::BankTransfer,
            'stripe_checkout_session_id' => null,
        ]);
    }
}
