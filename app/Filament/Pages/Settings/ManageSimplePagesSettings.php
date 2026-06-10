<?php

declare(strict_types=1);

namespace App\Filament\Pages\Settings;

use App\Settings\SimplePagesSettings;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSimplePagesSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Other pages';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Other pages';

    protected static ?string $slug = 'settings/pages';

    protected static function settings(): string
    {
        return SimplePagesSettings::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shop page')
                ->columns(2)
                ->components([
                    TextInput::make('shop_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('shop_hero_subtitle')->label('Text under the heading')->required()->maxLength(500),
                    TextInput::make('shop_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('shop_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Testimonials page')
                ->columns(2)
                ->components([
                    TextInput::make('testimonials_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('testimonials_hero_subtitle')->label('Text under the heading')->required()->maxLength(500),
                    TextInput::make('testimonials_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('testimonials_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Hall of Fame page')
                ->columns(2)
                ->components([
                    TextInput::make('hall_of_fame_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('hall_of_fame_hero_subtitle')->label('Text under the heading')->required()->maxLength(500),
                    TextInput::make('hall_of_fame_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('hall_of_fame_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Contact page')
                ->columns(2)
                ->components([
                    TextInput::make('contact_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('contact_hero_subtitle')->label('Text under the heading')->required()->maxLength(500),
                    TextInput::make('contact_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('contact_seo_description')->label('SEO description')->rows(2)->required(),
                    TextInput::make('contact_form_heading')->label('Form heading')->required()->maxLength(255),
                    TextInput::make('contact_direct_heading')->label('Direct contact heading')->required()->maxLength(255),
                    TextInput::make('contact_newsletter_heading')->label('Newsletter heading')->required()->maxLength(255),
                    TextInput::make('contact_newsletter_text')->label('Newsletter text')->required()->maxLength(255),
                ]),
            Section::make('Privacy policy')
                ->components([
                    TextInput::make('privacy_title')->label('Heading')->required()->maxLength(255),
                    RichEditor::make('privacy_body')->label('Content')->required(),
                ]),
            Section::make('Terms & conditions')
                ->components([
                    TextInput::make('terms_title')->label('Heading')->required()->maxLength(255),
                    RichEditor::make('terms_body')->label('Content')->required(),
                ]),
        ]);
    }
}
