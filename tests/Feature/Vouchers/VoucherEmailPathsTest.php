<?php

namespace Tests\Feature\Vouchers;

use App\Actions\EmailVoucher;
use App\Actions\IssuePurchasedVoucher;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * The gift-voucher email is sent from two places — the purchase webhook and the
 * admin "Email voucher" button. Both go through the one EmailVoucher action, so
 * a change to how the voucher is emailed can't reach one path and miss the other.
 */
class VoucherEmailPathsTest extends TestCase
{
    public function test_the_admin_email_button_sends_through_the_shared_action(): void
    {
        $this->actingAs(User::factory()->create());
        $voucher = Voucher::factory()->create();

        $this->mock(EmailVoucher::class, fn (MockInterface $mock) => $mock->shouldReceive('handle')
            ->once()->withArgs(fn (Voucher $v): bool => $v->is($voucher))->andReturnTrue());

        Livewire::test(ListVouchers::class)->callTableAction('sendEmail', $voucher)->assertNotified('Voucher emailed');
    }

    public function test_a_paid_purchase_sends_through_the_shared_action(): void
    {
        Mail::fake();
        $payment = Payment::factory()->create([
            'purpose' => PaymentPurpose::VoucherPurchase,
            'status' => PaymentStatus::Paid,
            'metadata' => ['purchaser_name' => 'Sam', 'purchaser_email' => 'sam@example.test'],
        ]);

        $this->mock(EmailVoucher::class, fn (MockInterface $mock) => $mock->shouldReceive('handle')->once()->andReturnTrue());

        app(IssuePurchasedVoucher::class)->handle($payment);
    }

    public function test_a_failed_admin_send_does_not_claim_the_voucher_was_emailed(): void
    {
        $this->actingAs(User::factory()->create());
        $voucher = Voucher::factory()->create();

        $this->mock(EmailVoucher::class, fn (MockInterface $mock) => $mock->shouldReceive('handle')->once()->andReturnFalse());

        Livewire::test(ListVouchers::class)->callTableAction('sendEmail', $voucher)->assertNotified('Voucher email failed');
    }
}
