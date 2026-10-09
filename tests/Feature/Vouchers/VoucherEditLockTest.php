<?php

namespace Tests\Feature\Vouchers;

use App\Enums\VoucherStatus;
use App\Filament\Resources\Vouchers\Pages\EditVoucher;
use App\Models\User;
use App\Models\Voucher;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A voucher is money. Its status is owned by the Redeem / Revoke actions, and a
 * bought or used voucher's value can't be rewritten from the edit form — so a
 * redeemed voucher can never be turned back into a spendable one.
 */
class VoucherEditLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_redeemed_voucher_cannot_be_made_spendable_again_from_the_form(): void
    {
        $voucher = Voucher::factory()->create([
            'source' => 'admin',
            'status' => VoucherStatus::Redeemed,
            'redeemed_at' => now(),
            'amount_pence' => 26000,
        ]);

        Livewire::test(EditVoucher::class, ['record' => $voucher->getRouteKey()])
            ->fillForm(['status' => VoucherStatus::Active->value, 'amount_pence' => 999])
            ->call('save');

        $voucher->refresh();
        $this->assertSame(VoucherStatus::Redeemed, $voucher->status);
        $this->assertSame(26000, $voucher->amount_pence);
        $this->assertFalse($voucher->isRedeemable());
    }

    public function test_a_bought_voucher_keeps_the_value_that_was_paid(): void
    {
        $voucher = Voucher::factory()->create(['source' => 'online', 'amount_pence' => 26000]);

        Livewire::test(EditVoucher::class, ['record' => $voucher->getRouteKey()])
            ->fillForm(['amount_pence' => 999])
            ->call('save');

        $this->assertSame(26000, $voucher->refresh()->amount_pence);
    }

    public function test_an_unused_hand_issued_voucher_can_still_be_corrected(): void
    {
        $voucher = Voucher::factory()->create(['source' => 'admin', 'amount_pence' => 26000]);

        Livewire::test(EditVoucher::class, ['record' => $voucher->getRouteKey()])
            ->fillForm(['amount_pence' => 300])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(30000, $voucher->refresh()->amount_pence);
    }
}
