<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasLabel
{
    case Tandem = 'tandem';
    case Aff = 'aff';
    case Coaching = 'coaching';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tandem => 'Tandem skydive',
            self::Aff => 'AFF course',
            self::Coaching => 'Coaching',
        };
    }
}
