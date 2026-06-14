<?php

namespace Tests\Feature\Account;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Voucher;
use App\Settings\JumpPrepSettings;
use Tests\TestCase;

class JumpDetailsTest extends TestCase
{
    public function test_upcoming_booking_shows_the_before_your_jump_info(): void
    {
        app(JumpPrepSettings::class)->fill(['what_to_bring' => 'BRING_SENTINEL trainers and ID'])->save();

        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addWeek(),
            'email' => $customer->email,
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/account/bookings/'.$booking->id)
            ->assertOk()
            ->assertSee('Before your jump')
            ->assertSee('BRING_SENTINEL trainers and ID');
    }

    public function test_dashboard_shows_redeemable_vouchers_the_customer_bought(): void
    {
        $customer = Customer::factory()->create(['email' => 'gifter@example.com']);
        Voucher::factory()->create([
            'purchaser_email' => 'gifter@example.com',
            'status' => VoucherStatus::Active,
            'expires_at' => now()->addMonths(6),
            'code' => 'GIFT-CODE-1',
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/account')
            ->assertOk()
            ->assertSee('Your gift vouchers')
            ->assertSee('GIFT-CODE-1');
    }

    public function test_past_booking_does_not_show_before_your_jump(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
            'scheduled_at' => now()->subWeek(),
            'email' => $customer->email,
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/account/bookings/'.$booking->id)
            ->assertOk()
            ->assertDontSee('Before your jump');
    }
}
