<?php

namespace App\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;

/**
 * The one way to build a date / datetime field in the admin panel.
 *
 * Filament's non-native (JavaScript) picker renders a *readonly* text field —
 * the owner can only click through the calendar, never type. Its native input
 * mode (`<input type="date">` / `datetime-local`) is keyboard-typeable AND
 * exposes the browser's calendar, so the owner can type "12/05/2026" or pick
 * it. Native inputs render in the browser's locale (dd/mm/yyyy in the UK) and
 * ignore a custom display format, which is why we don't set one here.
 *
 * Every date field in the panel is built through these factories so the
 * behaviour is identical everywhere and can never regress to the click-only
 * picker. Callers chain their own label / default / rules.
 */
class AdminDates
{
    public static function date(string $name): DatePicker
    {
        return DatePicker::make($name)
            ->native(true)
            ->firstDayOfWeek(1);
    }

    public static function dateTime(string $name): DateTimePicker
    {
        return DateTimePicker::make($name)
            ->native(true)
            ->seconds(false)
            ->firstDayOfWeek(1);
    }
}
