<?php

namespace App\Filament\Pages\Settings;

use App\Settings\AffPageSettings;
use App\Support\AdminPriceTokens;
use App\Support\ImageCrop;
use App\Support\SiteIcons;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageAffPageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'AFF page';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'AFF page';

    protected static ?string $slug = 'settings/aff';

    protected static function settings(): string
    {
        return AffPageSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Search engines (SEO)')
                ->columns(2)
                ->components([
                    TextInput::make('seo_title')->label('Page title')->required()->maxLength(255),
                    AdminPriceTokens::field(Textarea::make('seo_description')->label('Page description')->rows(2)->required()),
                ]),
            Section::make('Hero (top of page)')
                ->columns(2)
                ->components([
                    TextInput::make('hero_title')->label('Heading')->required()->maxLength(255),
                    AdminPriceTokens::field(TextInput::make('hero_subtitle')->label('Text under the heading')->maxLength(500)),
                    ImageCrop::ratio(
                        FileUpload::make('hero_image')
                            ->label('Hero photo')
                            ->helperText('Full-width banner photo — crop to 16:9. Leave empty to keep the current image.')
                            ->disk('public')
                            ->directory('pages')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        '16:9',
                    ),
                ]),
            Section::make('“The course” section')
                ->components([
                    TextInput::make('intro_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('intro_title')->label('Heading')->required()->maxLength(255),
                    Textarea::make('intro_lead')->label('Lead text')->rows(3),
                    Repeater::make('bullets')
                        ->label('Bullet points')
                        ->simple(TextInput::make('bullet')->required()->maxLength(255))
                        ->reorderable()
                        ->minItems(1),
                    ImageCrop::ratio(
                        FileUpload::make('intro_image')
                            ->label('Photo')
                            ->helperText('Shown beside the intro text — crop to 16:10. Leave empty to keep the current image.')
                            ->disk('public')
                            ->directory('pages')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        '16:10',
                    ),
                ]),
            Section::make('“Train with confidence” section')
                ->components([
                    TextInput::make('trust_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('trust_title')->label('Heading')->required()->maxLength(255),
                    Textarea::make('trust_body')->label('Text')->rows(3)->required(),
                ]),
            Section::make('Pricing section heading')
                ->columns(3)
                ->components([
                    TextInput::make('pricing_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('pricing_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('repeat_pricing_heading')->label('Repeat pricing heading')->required()->maxLength(255),
                ]),
            Section::make('“Upcoming courses” section')
                ->columns(2)
                ->components([
                    TextInput::make('courses_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('courses_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('courses_lead')->label('Lead text')->maxLength(500),
                    TextInput::make('courses_empty_text')->label('Text when no courses are open')->required()->maxLength(500),
                ]),
            Section::make('“Where & when” section')
                ->components([
                    TextInput::make('info_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('info_title')->label('Heading')->required()->maxLength(255),
                    Textarea::make('info_lead')->label('Lead text')->rows(2),
                    Repeater::make('info_cards')
                        ->label('Info cards')
                        ->columns(3)
                        ->components([
                            Select::make('icon')->options(SiteIcons::options())->required(),
                            TextInput::make('title')->required()->maxLength(255),
                            TextInput::make('body')->required()->maxLength(500),
                        ])
                        ->reorderable()
                        ->minItems(1),
                ]),
        ]);
    }
}
