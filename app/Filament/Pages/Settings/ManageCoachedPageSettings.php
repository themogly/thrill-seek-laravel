<?php

declare(strict_types=1);

namespace App\Filament\Pages\Settings;

use App\Settings\CoachedPageSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageCoachedPageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewfinderCircle;

    protected static ?string $navigationLabel = 'Coached skills page';

    protected static ?string $title = 'Coached skills page';

    protected static ?string $slug = 'settings/coached';

    protected static function settings(): string
    {
        return CoachedPageSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Search engines (SEO)')
                ->columns(2)
                ->components([
                    TextInput::make('seo_title')->label('Page title')->required()->maxLength(255),
                    Textarea::make('seo_description')->label('Page description')->rows(2)->required(),
                ]),
            Section::make('Hero (top of page)')
                ->columns(2)
                ->components([
                    TextInput::make('hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('hero_subtitle')->label('Text under the heading')->required()->maxLength(500),
                ]),
            Section::make('Main section')
                ->components([
                    TextInput::make('price_eyebrow')
                        ->label('Small line (price)')
                        ->helperText('e.g. “From £60 per session”.')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('heading')->label('Heading')->required()->maxLength(255),
                    Textarea::make('body')->label('Paragraph')->rows(4)->required(),
                    Repeater::make('skills')
                        ->label('Skills list')
                        ->simple(TextInput::make('skill')->required()->maxLength(255))
                        ->reorderable()
                        ->minItems(1),
                    FileUpload::make('image')
                        ->label('Photo')
                        ->helperText('Leave empty to keep the current image.')
                        ->image()
                        ->disk('public')
                        ->directory('pages')
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    TextInput::make('button_label')->label('Button text (links to Contact)')->required()->maxLength(100),
                ]),
        ]);
    }
}
