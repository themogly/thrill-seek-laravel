<?php

namespace App\Filament\Resources\HallOfFame\Schemas;

use App\Support\AdminDates;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HallOfFameEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('milestone')
                    ->label('Milestone')
                    ->helperText('What they achieved, e.g. “A Licence — Spain 2024”.')
                    ->required()
                    ->maxLength(255),
                AdminDates::date('achieved_on')
                    ->label('Date achieved (optional)'),
                TextInput::make('note')
                    ->label('Caption note (optional)')
                    ->helperText('A short extra line shown under the milestone.')
                    ->maxLength(255),
                FileUpload::make('image')
                    ->label('Photo')
                    ->helperText('Portrait-style photo works best (3:4). Leave empty to keep the current photo.')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('hall-of-fame')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->columnSpanFull(),
            ]);
    }
}
