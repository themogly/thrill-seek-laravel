<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnquiryStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Replied = 'replied';
    case PaymentSent = 'payment_sent';
    case Converted = 'converted';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Replied => 'Replied',
            self::PaymentSent => 'Payment sent',
            self::Converted => 'Converted',
            self::Closed => 'Closed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Replied => 'info',
            self::PaymentSent => 'primary',
            self::Converted => 'success',
            self::Closed => 'gray',
        };
    }
}
