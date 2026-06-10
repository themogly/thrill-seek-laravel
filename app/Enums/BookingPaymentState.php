<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Derived payment position of a booking — never stored, always computed
 * from the booking's paid payments vs its price.
 */
enum BookingPaymentState: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case DepositPaid = 'deposit_paid';
    case PaidInFull = 'paid_in_full';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::DepositPaid => 'Deposit paid — balance outstanding',
            self::PaidInFull => 'Paid in full',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::DepositPaid => 'warning',
            self::PaidInFull => 'success',
        };
    }
}
