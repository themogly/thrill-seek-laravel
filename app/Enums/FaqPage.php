<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Which service page a FAQ belongs to. FAQs are per-page (the tandem and AFF
 * answers differ), shown as a section on that page and emitted as its FAQPage
 * structured data.
 */
enum FaqPage: string implements HasLabel
{
    case Tandem = 'tandem';
    case Aff = 'aff';
    case Coached = 'coached';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tandem => 'Tandem page',
            self::Aff => 'AFF page',
            self::Coached => 'Coached skills page',
        };
    }
}
