<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VoucherStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Redeemed = 'redeemed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Redeemed => 'Redeemed',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Redeemed => 'info',
            self::Expired => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
