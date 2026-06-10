<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentPurpose: string implements HasLabel
{
    case TandemFull = 'tandem_full';
    case AffDeposit = 'aff_deposit';
    case AffBalance = 'aff_balance';
    case VoucherPurchase = 'voucher_purchase';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::TandemFull => 'Tandem — full payment',
            self::AffDeposit => 'AFF — deposit',
            self::AffBalance => 'AFF — balance',
            self::VoucherPurchase => 'Gift voucher purchase',
            self::Custom => 'Custom amount',
        };
    }
}
