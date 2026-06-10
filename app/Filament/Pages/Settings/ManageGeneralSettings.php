<?php

declare(strict_types=1);

namespace App\Filament\Pages\Settings;

use App\Settings\GeneralSettings;
use App\Support\SiteIcons;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGeneralSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'General';

    protected static ?string $title = 'General settings';

    protected static ?string $slug = 'settings/general';

    protected static function settings(): string
    {
        return GeneralSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Site identity')
                ->columns(2)
                ->components([
                    TextInput::make('site_name')
                        ->label('Site name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('tagline')
                        ->label('Tagline')
                        ->helperText('Shown in the footer under the logo.')
                        ->required()
                        ->maxLength(255),
                ]),
            Section::make('Contact details')
                ->columns(2)
                ->components([
                    TextInput::make('phone')
                        ->label('Phone number')
                        ->helperText('Displayed exactly as written here.')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('email')
                        ->label('Email address')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]),
            Section::make('Social media')
                ->columns(2)
                ->components([
                    TextInput::make('instagram_url')
                        ->label('Instagram URL')
                        ->url()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('facebook_url')
                        ->label('Facebook URL')
                        ->url()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('instagram_handle')
                        ->label('Instagram handle')
                        ->helperText('e.g. “@gforceskydiving” — shown on the home page feed.')
                        ->required()
                        ->maxLength(255),
                ]),
            Section::make('Search engines & sharing (SEO)')
                ->components([
                    TextInput::make('seo_title')
                        ->label('Default page title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('seo_description')
                        ->label('Default page description')
                        ->rows(3)
                        ->required(),
                    TextInput::make('og_image')
                        ->label('Social sharing image URL')
                        ->helperText('Image shown when the site is shared on social media.')
                        ->url()
                        ->required()
                        ->maxLength(2048),
                ]),
            Section::make('Footer')
                ->components([
                    TextInput::make('footer_copyright')
                        ->label('Copyright line')
                        ->required()
                        ->maxLength(255),
                ]),
            Section::make('Trust badges')
                ->description('The four cards shown in the “Why jump with us” strip on the home and AFF pages.')
                ->components([
                    Repeater::make('trust_items')
                        ->hiddenLabel()
                        ->columns(3)
                        ->components([
                            Select::make('icon')->options(SiteIcons::options())->required(),
                            TextInput::make('value')->label('Big text')->required()->maxLength(100),
                            TextInput::make('label')->label('Caption')->required()->maxLength(255),
                        ])
                        ->reorderable()
                        ->minItems(1),
                ]),
        ]);
    }
}
