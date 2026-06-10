<?php

declare(strict_types=1);

namespace App\Filament\Pages\Settings;

use App\Settings\TandemPageSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageTandemPageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Tandem page';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Tandem page';

    protected static ?string $slug = 'settings/tandem';

    protected static function settings(): string
    {
        return TandemPageSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Search engines & sharing (SEO)')
                ->columns(2)
                ->components([
                    TextInput::make('seo_title')->label('Page title')->required()->maxLength(255),
                    Textarea::make('seo_description')->label('Page description')->rows(2)->required(),
                    TextInput::make('og_title')->label('Social sharing title')->required()->maxLength(255),
                    TextInput::make('og_description')->label('Social sharing description')->required()->maxLength(500),
                ]),
            Section::make('Hero (top of page)')
                ->columns(2)
                ->components([
                    TextInput::make('hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('hero_subtitle')->label('Text under the heading')->required()->maxLength(500),
                    FileUpload::make('hero_image')
                        ->label('Hero photo')
                        ->helperText('Full-width banner photo. Leave empty to keep the current image.')
                        ->image()
                        ->disk('public')
                        ->directory('pages')
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ]),
            Section::make('“The jump” section')
                ->components([
                    TextInput::make('intro_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('intro_title')->label('Heading')->required()->maxLength(255),
                    Textarea::make('intro_lead')->label('Lead text')->rows(3)->required(),
                    Repeater::make('bullets')
                        ->label('Bullet points')
                        ->simple(TextInput::make('bullet')->required()->maxLength(255))
                        ->reorderable()
                        ->minItems(1),
                    TextInput::make('locations_heading')->label('Locations heading')->required()->maxLength(255),
                    Repeater::make('locations')
                        ->label('Locations')
                        ->simple(TextInput::make('location')->required()->maxLength(255))
                        ->reorderable()
                        ->minItems(1),
                    FileUpload::make('intro_image')
                        ->label('Photo')
                        ->helperText('Leave empty to keep the current image.')
                        ->image()
                        ->disk('public')
                        ->directory('pages')
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ]),
            Section::make('Pricing section heading')
                ->columns(2)
                ->components([
                    TextInput::make('pricing_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('pricing_title')->label('Heading')->required()->maxLength(255),
                ]),
            Section::make('Charity note')
                ->columns(2)
                ->components([
                    TextInput::make('charity_note_title')->label('Bold lead-in')->required()->maxLength(255),
                    TextInput::make('charity_note_body')->label('Text')->required()->maxLength(500),
                ]),
        ]);
    }
}
