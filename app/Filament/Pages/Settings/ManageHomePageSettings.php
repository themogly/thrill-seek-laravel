<?php

namespace App\Filament\Pages\Settings;

use App\Settings\HomePageSettings;
use App\Support\ImageCrop;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageHomePageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Home page';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Home page';

    protected static ?string $slug = 'settings/home';

    protected static function settings(): string
    {
        return HomePageSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hero (top of page)')
                ->columns(2)
                ->components([
                    TextInput::make('hero_eyebrow')
                        ->label('Small line above the heading')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('hero_subtitle')
                        ->label('Text under the heading')
                        ->required()
                        ->maxLength(500),
                    TextInput::make('hero_title_1')
                        ->label('Heading — part 1')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('hero_title_highlight')
                        ->label('Heading — highlighted part (orange)')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('hero_title_2')
                        ->label('Heading — part 2')
                        ->required()
                        ->maxLength(100),
                    ImageCrop::ratio(
                        FileUpload::make('hero_image')
                            ->label('Background image')
                            ->helperText('Full-width hero — crop to 16:9. Leave empty to keep the current image.')
                            ->disk('public')
                            ->directory('pages')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        '16:9',
                    ),
                    TextInput::make('hero_cta_primary_label')
                        ->label('Orange button text (links to Tandem)')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('hero_cta_secondary_label')
                        ->label('Outline button text (links to AFF)')
                        ->required()
                        ->maxLength(100),
                ]),
            Section::make('“What we do” section heading')
                ->columns(3)
                ->components([
                    TextInput::make('services_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('services_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('services_lead')->label('Lead text')->maxLength(500),
                ]),
            Section::make('“Our story” section')
                ->components([
                    TextInput::make('about_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('about_title')->label('Heading')->required()->maxLength(255),
                    Textarea::make('about_body')->label('Paragraph')->rows(4)->required(),
                    ImageCrop::ratio(
                        FileUpload::make('about_image_1')
                            ->label('Left photo')
                            ->helperText('Portrait photo — crop to 3:4. Leave empty to keep the current image.')
                            ->disk('public')
                            ->directory('pages')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        '3:4',
                    ),
                    ImageCrop::ratio(
                        FileUpload::make('about_image_2')
                            ->label('Right photo')
                            ->helperText('Portrait photo — crop to 3:4. Leave empty to keep the current image.')
                            ->disk('public')
                            ->directory('pages')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        '3:4',
                    ),
                ]),
            Section::make('“Why jump with us” heading')
                ->columns(2)
                ->components([
                    TextInput::make('trust_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('trust_title')->label('Heading')->required()->maxLength(255),
                ]),
            Section::make('“Meet the team” teaser')
                ->components([
                    TextInput::make('team_lead')->label('Lead text (shown above the “Meet the Team” button on the home page)')->maxLength(500),
                ]),
            Section::make('“Follow us” card')
                ->description('The “Latest News” block next to it is managed under News.')
                ->components([
                    TextInput::make('instagram_caption')
                        ->label('Instagram caption')
                        ->required()
                        ->maxLength(255),
                ]),
            Section::make('“Real reviews” heading')
                ->columns(2)
                ->components([
                    TextInput::make('testimonials_eyebrow')->label('Small line')->required()->maxLength(255),
                    TextInput::make('testimonials_title')->label('Heading')->required()->maxLength(255),
                ]),
            Section::make('Newsletter banner')
                ->columns(2)
                ->components([
                    TextInput::make('newsletter_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('newsletter_subtitle')->label('Text')->maxLength(500),
                ]),
            Section::make('“Ready to jump?” box')
                ->columns(3)
                ->components([
                    TextInput::make('cta_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('cta_subtitle')->label('Text')->maxLength(500),
                    TextInput::make('cta_button_label')->label('Button text (links to Contact)')->required()->maxLength(100),
                ]),
        ]);
    }
}
