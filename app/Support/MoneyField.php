<?php

namespace App\Support;

use Filament\Forms\Components\TextInput;

/**
 * The one way to enter money in the admin. Storage stays integer **pence**
 * everywhere (Stripe charges in the smallest unit, so pence is the correct
 * canonical form and no read site changes); this presenter shows and accepts
 * **pounds** with a £ prefix and 2 decimals, converting only at the form edge:
 * pence → pounds on load, pounds → pence (rounded) on save. Bind it to the
 * `*_pence` column.
 */
class MoneyField
{
    public static function pounds(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->prefix('£')
            ->step('0.01')
            ->minValue(0)
            ->formatStateUsing(fn (mixed $state): mixed => filled($state) ? number_format(((int) $state) / 100, 2, '.', '') : $state)
            ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state) ? (int) round(((float) $state) * 100) : null);
    }
}
