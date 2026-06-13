<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * In-panel, plain-English guide for the (non-technical) owner. This is static
 * documentation, not CMS content — edit the Blade view to change it.
 */
class HelpGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Help';

    protected static ?string $navigationLabel = 'How it all works';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'How it all works';

    protected string $view = 'filament.pages.help-guide';
}
