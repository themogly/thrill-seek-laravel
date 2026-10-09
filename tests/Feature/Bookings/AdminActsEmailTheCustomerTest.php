<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin-originated acts email the customer the way the online paths do, behind an
 * "Email the customer" toggle that defaults on (Ben, 9 Oct 2026, option B). The
 * same actions send the same mail — there is no second send path.
 */
class AdminActsEmailTheCustomerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_creating_a_confirmed_booking_sends_the_confirmation_by_default(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm(['name' => 'Sam Phone', 'email' => 'sam@example.test', 'price_pence' => 260, 'status' => BookingStatus::Confirmed->value])
            ->assertFormFieldIsVisible('notify_customer')
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $booking = Booking::where('email', 'sam@example.test')->sole();
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $m): bool => $m->hasTo('sam@example.test')
            && str_contains($m->renderedSubject, 'confirmed')
            && str_contains($m->renderedBody, $booking->reference));
    }

    public function test_creating_a_confirmed_booking_with_the_toggle_off_sends_nothing(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm(['name' => 'Backfill', 'email' => 'old@example.test', 'price_pence' => 260, 'status' => BookingStatus::Confirmed->value, 'notify_customer' => false])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Booking::where('email', 'old@example.test')->count());
        Mail::assertNothingQueued();
    }

    public function test_creating_an_unconfirmed_booking_offers_no_toggle_and_sends_nothing(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm(['name' => 'Pending', 'email' => 'pending@example.test', 'price_pence' => 260, 'status' => BookingStatus::PendingDate->value])
            ->assertFormFieldIsHidden('notify_customer')
            ->call('create');

        Mail::assertNothingQueued();
    }

    public function test_editing_an_existing_confirmed_booking_sends_nothing(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->assertFormFieldIsHidden('notify_customer')
            ->fillForm(['notes' => 'Moved to the morning load.'])
            ->call('save')
            ->assertHasNoFormErrors();

        Mail::assertNothingQueued();
    }

    public function test_redeeming_a_voucher_emails_the_receipt_by_default(): void
    {
        $booking = Booking::factory()->confirmed()->create(['price_pence' => 26000, 'email' => 'jumper@example.test']);
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);

        Livewire::test(ListVouchers::class)
            ->callTableAction('redeem', $voucher, ['booking_id' => $booking->id])
            ->assertHasNoTableActionErrors();

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $m): bool => $m->hasTo('jumper@example.test')
            && str_contains($m->renderedSubject, 'Payment received')
            && str_contains($m->renderedBody, '£260'));
        Mail::assertQueued(PaymentReceivedAdminNotification::class, 1);
    }

    public function test_redeeming_a_voucher_with_the_toggle_off_sends_nothing(): void
    {
        $booking = Booking::factory()->confirmed()->create(['price_pence' => 26000]);
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);

        Livewire::test(ListVouchers::class)
            ->callTableAction('redeem', $voucher, ['booking_id' => $booking->id, 'notify' => false])
            ->assertHasNoTableActionErrors();

        $this->assertSame('redeemed', $voucher->refresh()->status->value);
        Mail::assertNothingQueued();
    }
}
