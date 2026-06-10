<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MessageDirection: string implements HasLabel
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inbound => 'From customer',
            self::Outbound => 'From us',
        };
    }
}
