<?php

namespace Tests\Feature\Pages;

use App\Models\Booking;
use App\Models\Payment;
use Tests\TestCase;

/**
 * Pins both resolution strategies of the payment-success page — Stripe
 * session id vs voucher-booking claim check — and their precedence, so the
 * lookup logic can be moved without changing what customers see.
 */
class PaymentSuccessResolutionTest extends TestCase
{
    public function test_resolves_by_stripe_session_id(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        Payment::factory()->paid()->create([
            'booking_id' => $booking->id,
            'stripe_checkout_session_id' => 'cs_resolution_1',
        ]);

        $this->get('/payment/success?session_id=cs_resolution_1')
            ->assertOk()
            ->assertSee($booking->reference);
    }

    public function test_resolves_by_booking_reference_case_insensitively(): void
    {
        $booking = Booking::factory()->confirmed()->create(['reference' => 'BK-CLAIM1']);
        Payment::factory()->paid()->bankTransfer()->create(['booking_id' => $booking->id]);

        $this->get('/payment/success?booking=bk-claim1')
            ->assertOk()
            ->assertSee('BK-CLAIM1');
    }

    public function test_session_id_takes_precedence_over_a_booking_reference(): void
    {
        $stripeBooking = Booking::factory()->confirmed()->create(['reference' => 'BK-STRIPE']);
        Payment::factory()->paid()->create([
            'booking_id' => $stripeBooking->id,
            'stripe_checkout_session_id' => 'cs_resolution_2',
        ]);
        Booking::factory()->confirmed()->create(['reference' => 'BK-OTHERB']);

        $this->get('/payment/success?session_id=cs_resolution_2&booking=BK-OTHERB')
            ->assertOk()
            ->assertSee('BK-STRIPE')
            ->assertDontSee('BK-OTHERB');
    }

    public function test_a_pending_payment_shows_the_processing_state(): void
    {
        $booking = Booking::factory()->create();
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'stripe_checkout_session_id' => 'cs_resolution_3',
        ]);

        $this->get('/payment/success?session_id=cs_resolution_3')
            ->assertOk()
            ->assertSee('being confirmed');
    }

    public function test_unknown_session_falls_back_to_the_generic_thank_you(): void
    {
        $this->get('/payment/success?session_id=cs_who_knows')
            ->assertOk()
            ->assertSee('your payment went through');
    }

    public function test_no_parameters_still_renders(): void
    {
        $this->get('/payment/success')->assertOk();
    }
}
