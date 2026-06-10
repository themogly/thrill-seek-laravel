<?php

declare(strict_types=1);

namespace App\Filament\Resources\Testimonials\Schemas;

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
                Toggle::make('featured')
                    ->label('Show on home page')
                    ->helperText('The first three featured testimonials appear on the home page.'),
            ]);
    }
}
