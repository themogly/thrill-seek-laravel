<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShopItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShopItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('price_label')
                    ->label('Price')
                    ->helperText('Shown exactly as written, e.g. “£15 – £30” or “£12”.')
                    ->required()
                    ->maxLength(50),
                TextInput::make('description')
                    ->label('Description')
                    ->helperText('One short sentence shown under the name.')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
