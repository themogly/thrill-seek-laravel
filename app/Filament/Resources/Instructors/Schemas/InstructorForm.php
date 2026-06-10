<?php

namespace App\Filament\Resources\Instructors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstructorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('role')
                    ->label('Role')
                    ->helperText('Their job title, e.g. “Chief Instructor”.')
                    ->required()
                    ->maxLength(255),
                Textarea::make('bio')
                    ->label('Bio')
                    ->helperText('A short paragraph shown under their name.')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                FileUpload::make('photo')
                    ->label('Photo')
                    ->helperText('Optional. Leave blank to show the initial-letter badge instead.')
                    ->avatar()
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('instructors')
                    ->columnSpanFull(),
            ]);
    }
}
