<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NewsletterStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Unsubscribed = 'unsubscribed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending confirmation',
            self::Confirmed => 'Confirmed',
            self::Unsubscribed => 'Unsubscribed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Unsubscribed => 'gray',
        };
    }
}
