<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Models\Booking;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Awaiting payment" belongs to the Stripe webhook: it confirms or releases a
 * held place only while the booking is still in that state. The admin can
 * neither put a booking into it nor move one out of it by hand.
 */
class BookingStatusFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_the_admin_cannot_set_a_booking_to_awaiting_payment(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->fillForm(['status' => BookingStatus::PendingPayment->value])
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(BookingStatus::Confirmed, $booking->refresh()->status);
    }

    public function test_a_held_booking_awaiting_the_webhook_keeps_its_status(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::PendingPayment]);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->assertFormFieldIsDisabled('status')
            ->fillForm(['status' => BookingStatus::Confirmed->value])
            ->call('save');

        $this->assertSame(BookingStatus::PendingPayment, $booking->refresh()->status);
    }

    public function test_a_new_booking_cannot_start_as_awaiting_payment(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm(['name' => 'Sam', 'email' => 'sam@example.test', 'price_pence' => 260, 'status' => BookingStatus::PendingPayment->value])
            ->call('create')
            ->assertHasFormErrors(['status']);
    }
}
