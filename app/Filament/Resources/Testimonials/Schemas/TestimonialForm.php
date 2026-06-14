<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->helperText('How the reviewer is credited, e.g. “Sarah M.”')
                    ->required()
                    ->maxLength(255),
                TextInput::make('role')
                    ->label('Role')
                    ->helperText('What they did, e.g. “Tandem jumper” or “AFF graduate”.')
                    ->required()
                    ->maxLength(255),
                Select::make('rating')
                    ->label('Star rating (optional)')
                    ->helperText('Shown as stars near the name. Leave blank to hide.')
                    ->options([1 => '1 ★', 2 => '2 ★', 3 => '3 ★', 4 => '4 ★', 5 => '5 ★']),
                FileUpload::make('avatar')
                    ->label('Headshot (optional)')
                    ->helperText('Small round avatar. Leave blank to show the initial-letter badge instead.')
                    ->avatar()
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('testimonials'),
                FileUpload::make('photo')
                    ->label('Action photo (optional)')
                    ->helperText('A large jump/action shot. When set, the testimonial renders as a full-bleed photo tile.')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('testimonials-photos')
                    ->columnSpanFull(),
                Textarea::make('quote')
                    ->label('Quote')
                    ->helperText('The full review shown on the Testimonials page.')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->label('Short version (optional)')
                    ->helperText('Used on the home page instead of the full quote. Leave blank to show the full quote.')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('approved')
                    ->label('Approved (visible on the site)')
                    ->helperText('Customer-submitted reviews start unapproved — turn this on to publish.')
                    ->default(true),
                Toggle::make('featured')
                    ->label('Show on home page')
                    ->helperText('The first three featured testimonials appear on the home page.'),
            ]);
    }
}
