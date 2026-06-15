<?php

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

    /**
     * Stamp the privacy "last updated" date automatically whenever the policy
     * body changes, so the public page's date stays truthful without the owner
     * having to remember to set it (spatie's settings row has no usable
     * per-property updated_at — see App\ViewModels\PrivacyPage).
     */
    public function save(): void
    {
        $previousBody = app(SimplePagesSettings::class)->privacy_body;

        parent::save();

        if (($this->form->getState()['privacy_body'] ?? null) !== $previousBody) {
            $settings = app(SimplePagesSettings::class);
            $settings->privacy_updated_at = now()->toDateString();
            $settings->save();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shop page')
                ->columns(2)
                ->components([
                    TextInput::make('shop_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('shop_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('shop_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('shop_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Testimonials page')
                ->columns(2)
                ->components([
                    TextInput::make('testimonials_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('testimonials_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('testimonials_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('testimonials_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Hall of Fame page')
                ->columns(2)
                ->components([
                    TextInput::make('hall_of_fame_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('hall_of_fame_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('hall_of_fame_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('hall_of_fame_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Meet the Team page')
                ->columns(2)
                ->components([
                    TextInput::make('meet_the_team_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('meet_the_team_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('meet_the_team_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('meet_the_team_seo_description')->label('SEO description')->rows(2)->required(),
                    TextInput::make('home_team_teaser_line')
                        ->label('Homepage team teaser line')
                        ->helperText('The short trust line shown beside the team avatars on the homepage.')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
            Section::make('Contact page')
                ->columns(2)
                ->components([
                    TextInput::make('contact_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('contact_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('contact_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('contact_seo_description')->label('SEO description')->rows(2)->required(),
                    TextInput::make('contact_form_heading')->label('Form heading')->required()->maxLength(255),
                    TextInput::make('contact_direct_heading')->label('Direct contact heading')->required()->maxLength(255),
                    TextInput::make('contact_newsletter_heading')->label('Newsletter heading')->required()->maxLength(255),
                    TextInput::make('contact_newsletter_text')->label('Newsletter text')->required()->maxLength(255),
                ]),
            Section::make('Tandem booking page')
                ->columns(2)
                ->components([
                    TextInput::make('booking_tandem_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('booking_tandem_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('booking_tandem_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('booking_tandem_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('AFF booking page')
                ->columns(2)
                ->components([
                    TextInput::make('booking_aff_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('booking_aff_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('booking_aff_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('booking_aff_seo_description')->label('SEO description')->rows(2)->required(),
                ]),
            Section::make('Gift voucher page')
                ->columns(2)
                ->components([
                    TextInput::make('voucher_hero_title')->label('Heading')->required()->maxLength(255),
                    TextInput::make('voucher_hero_subtitle')->label('Text under the heading')->maxLength(500),
                    TextInput::make('voucher_seo_title')->label('SEO page title')->required()->maxLength(255),
                    Textarea::make('voucher_seo_description')->label('SEO description')->rows(2)->required(),
                    Textarea::make('voucher_intro')->label('Intro text above the form')->rows(2)->columnSpanFull(),
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
