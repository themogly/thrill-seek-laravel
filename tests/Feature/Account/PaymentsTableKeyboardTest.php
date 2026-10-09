<?php

namespace Tests\Feature\Account;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use Tests\TestCase;

/**
 * Prompt 024: at 390px the payments table is wider than the screen, so its box
 * scrolls sideways — and a keyboard user must be able to reach it to scroll it
 * (axe `scrollable-region-focusable`). The region is focusable and named.
 */
class PaymentsTableKeyboardTest extends TestCase
{
    public function test_the_payments_scroll_region_is_focusable_and_named(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id, 'email' => $customer->email]);
        Payment::factory()->create(['booking_id' => $booking->id, 'status' => PaymentStatus::Paid, 'paid_at' => now()]);

        $html = $this->actingAs($customer, 'customer')->get('/account/payments')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<div class="[^"]*overflow-x-auto[^"]*focus-visible:ring-2[^"]*"\s+tabindex="0"\s+role="region"\s+aria-label="Payments">\s*<table/',
            (string) $html,
        );
    }
}
