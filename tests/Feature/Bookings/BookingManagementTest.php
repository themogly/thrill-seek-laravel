<?php

declare(strict_types=1);

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\TandemDate;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_create_a_booking(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm([
                'name' => 'Walk-in Customer',
                'email' => 'walkin@example.com',
                'status' => BookingStatus::PendingDate->value,
                'price_pence' => 26000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::sole();
        $this->assertStringStartsWith('BK-', $booking->reference);
        $this->assertSame(BookingStatus::PendingDate, $booking->status);
    }

    public function test_assigning_a_slot_schedules_and_confirms_the_booking(): void
    {
        $slot = TandemDate::factory()->create(['capacity' => 4]);
        $booking = Booking::factory()->create();

        $booking->update(['tandem_date_id' => $slot->id]);

        $booking->refresh();
        $this->assertTrue($booking->scheduled_at->equalTo($slot->starts_at));
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(3, $slot->refresh()->remaining_capacity);
    }

    public function test_cancelled_bookings_free_up_slot_capacity(): void
    {
        $slot = TandemDate::factory()->create(['capacity' => 2]);
        Booking::factory()->count(2)->create(['tandem_date_id' => $slot->id]);

        $this->assertTrue($slot->refresh()->isFull());

        Booking::first()->update(['status' => BookingStatus::Cancelled]);

        $this->assertFalse($slot->refresh()->isFull());
        $this->assertSame(1, $slot->remaining_capacity);
    }

    public function test_rescheduling_emails_the_customer(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        $slot = TandemDate::factory()->create(['capacity' => 4]);

        Livewire::test(ListBookings::class)
            ->callTableAction('reschedule', $booking, [
                'tandem_date_id' => $slot->id,
                'notify' => true,
            ]);

        $booking->refresh();
        $this->assertSame(BookingStatus::Rescheduled, $booking->status);
        $this->assertTrue($booking->scheduled_at->equalTo($slot->starts_at));

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($booking->email)
            && str_contains($mail->renderedSubject, 'rescheduled'));
    }

    public function test_rescheduling_without_notification_sends_no_email(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->callTableAction('reschedule', $booking, [
                'scheduled_at' => now()->addWeeks(3)->setTime(10, 0)->toDateTimeString(),
                'notify' => false,
            ]);

        $this->assertSame(BookingStatus::Rescheduled, $booking->refresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_outstanding_balance_filter_finds_underpaid_bookings(): void
    {
        $paid = Booking::factory()->create(['price_pence' => 10000]);
        $paid->payments()->create([
            'purpose' => 'tandem_full',
            'method' => 'bank_transfer',
            'status' => 'paid',
            'amount_pence' => 10000,
            'paid_at' => now(),
        ]);
        $unpaid = Booking::factory()->create(['price_pence' => 175000]);

        $results = Booking::withOutstandingBalance()->get();

        $this->assertTrue($results->contains($unpaid));
        $this->assertFalse($results->contains($paid));
    }
}
