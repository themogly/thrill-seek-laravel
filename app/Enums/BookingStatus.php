<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case PendingDate = 'pending_date';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::PendingDate => 'Awaiting date',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rescheduled => 'Rescheduled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'gray',
            self::PendingDate => 'warning',
            self::Confirmed => 'success',
            self::Completed => 'info',
            self::Cancelled => 'danger',
            self::Rescheduled => 'primary',
        };
    }
}
