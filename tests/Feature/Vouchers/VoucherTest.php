<?php

declare(strict_types=1);

namespace Tests\Feature\Vouchers;

use App\Actions\RedeemVoucher;
use App\Enums\PaymentMethod;
use App\Enums\VoucherStatus;
use App\Filament\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_create_a_voucher_with_a_generated_code(): void
    {
        Livewire::test(CreateVoucher::class)
            ->fillForm([
                'amount_pence' => 26000,
                'expires_at' => now()->addYear()->toDateString(),
                'purchaser_name' => 'Gift Giver',
                'purchaser_email' => 'giver@example.com',
                'recipient_name' => 'Lucky Friend',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $voucher = Voucher::sole();
        $this->assertStringStartsWith('GV-', $voucher->code);
        $this->assertTrue($voucher->isRedeemable());
    }

    public function test_redeeming_applies_a_paid_voucher_payment_to_the_booking(): void
    {
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);
        $booking = Booking::factory()->create(['price_pence' => 26000]);

        app(RedeemVoucher::class)->handle($voucher, $booking, User::first());

        $voucher->refresh();
        $this->assertSame(VoucherStatus::Redeemed, $voucher->status);
        $this->assertSame($booking->id, $voucher->booking_id);

        $payment = $booking->payments()->sole();
        $this->assertSame(PaymentMethod::Voucher, $payment->method);
        $this->assertSame($voucher->code, $payment->reference);
        $this->assertFalse($booking->refresh()->hasOutstandingBalance());
    }

    public function test_expired_vouchers_cannot_be_redeemed(): void
    {
        $voucher = Voucher::factory()->expired()->create();
        $booking = Booking::factory()->create();

        $this->assertSame(VoucherStatus::Expired, $voucher->display_status);

        $this->expectException(InvalidArgumentException::class);
        app(RedeemVoucher::class)->handle($voucher, $booking, User::first());
    }

    public function test_redeemed_vouchers_cannot_be_redeemed_twice(): void
    {
        $voucher = Voucher::factory()->create();
        $booking = Booking::factory()->create();

        app(RedeemVoucher::class)->handle($voucher, $booking, User::first());

        $this->expectException(InvalidArgumentException::class);
        app(RedeemVoucher::class)->handle($voucher->refresh(), $booking, User::first());
    }

    public function test_voucher_email_contains_the_code_and_expiry(): void
    {
        $voucher = Voucher::factory()->create(['amount_pence' => 26000]);

        Livewire::test(ListVouchers::class)
            ->callTableAction('sendEmail', $voucher);

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($voucher->purchaser_email)
            && str_contains($mail->renderedBody, $voucher->code)
            && str_contains($mail->renderedBody, '£260'));
    }
}
