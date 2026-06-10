<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomatedEmailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
    }

    public function test_confirming_a_booking_emails_the_customer(): void
    {
        $booking = Booking::factory()->create();

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addMonth(),
        ]);

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($booking->email)
            && str_contains($mail->renderedSubject, 'confirmed'));
    }

    public function test_other_status_changes_do_not_email(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        $booking->update(['status' => BookingStatus::Completed]);

        Mail::assertNothingQueued();
    }

    public function test_jump_reminders_go_out_once_for_upcoming_bookings(): void
    {
        $upcoming = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(3),
        ]);
        Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(30),
        ]);
        Booking::factory()->create(['scheduled_at' => null]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($upcoming->email)
            && str_contains($mail->renderedSubject, 'coming up'));
        $this->assertNotNull($upcoming->refresh()->reminder_sent_at);

        // Running again must not re-send.
        Mail::fake();
        $this->artisan('bookings:send-reminders')->assertSuccessful();
        Mail::assertNothingQueued();
    }

    public function test_balance_reminders_target_underpaid_upcoming_bookings(): void
    {
        $underpaid = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(10),
            'price_pence' => 175000,
        ]);
        $underpaid->payments()->create([
            'purpose' => 'aff_deposit',
            'method' => 'bank_transfer',
            'status' => 'paid',
            'amount_pence' => 30000,
            'paid_at' => now(),
        ]);

        $settled = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(10),
            'price_pence' => 26000,
        ]);
        $settled->payments()->create([
            'purpose' => 'tandem_full',
            'method' => 'stripe',
            'status' => 'paid',
            'amount_pence' => 26000,
            'paid_at' => now(),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($underpaid->email)
            && str_contains($mail->renderedBody, '£1,450'));
        Mail::assertNotQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($settled->email)
            && str_contains($mail->renderedSubject, 'Balance due'));
    }
}
