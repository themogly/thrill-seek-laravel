<?php

namespace App\Filament\Pages\Settings;

use App\Settings\JumpPrepSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageJumpPrepSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInformationCircle;

    protected static ?string $navigationLabel = 'Before-your-jump info';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Before-your-jump info';

    protected static ?string $slug = 'settings/jump-prep';

    protected static function settings(): string
    {
        return JumpPrepSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shown to customers with an upcoming jump')
                ->description('Appears on the customer account dashboard and booking page. Keep it practical and reassuring.')
                ->components([
                    Textarea::make('arrival_info')->label('Arrival & timing')->rows(3)->required(),
                    Textarea::make('what_to_bring')->label('What to bring / wear')->rows(3)->required(),
                    Textarea::make('what_to_expect')->label('What to expect')->rows(3)->required(),
                ]),
        ]);
    }
}
