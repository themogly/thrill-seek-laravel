<?php

declare(strict_types=1);

namespace App\Filament\Resources\GalleryImages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GalleryImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('Image')
                    ->helperText('Square images work best. Leave empty to keep the current image.')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('gallery')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->columnSpanFull(),
                TextInput::make('alt_text')
                    ->label('Image description')
                    ->helperText('Read aloud by screen readers; not visible on the page.')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
