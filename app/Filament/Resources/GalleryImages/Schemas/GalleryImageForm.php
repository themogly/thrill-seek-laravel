<?php

namespace App\Filament\Resources\GalleryImages\Schemas;

use App\Support\AdminImages;
use App\Support\ImageCrop;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GalleryImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageCrop::ratio(
                    AdminImages::upload('image')
                        ->label('Image')
                        ->helperText('Shown in the square gallery grid — crop to 1:1. Leave empty to keep the current image.')
                        ->disk('public')
                        ->directory('gallery')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->columnSpanFull(),
                    '1:1',
                ),
                TextInput::make('alt_text')
                    ->label('Image description')
                    ->helperText('Read aloud by screen readers; not visible on the page.')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
