<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Stripe = 'stripe';
    case BankTransfer = 'bank_transfer';
    case Voucher = 'voucher';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::BankTransfer => 'Bank transfer',
            self::Voucher => 'Gift voucher',
        };
    }
}
