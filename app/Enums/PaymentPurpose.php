<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentPurpose: string implements HasLabel
{
    case TandemFull = 'tandem_full';
    case AffDeposit = 'aff_deposit';
    case AffBalance = 'aff_balance';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::TandemFull => 'Tandem — full payment',
            self::AffDeposit => 'AFF — deposit',
            self::AffBalance => 'AFF — balance',
            self::Custom => 'Custom amount',
        };
    }
}
