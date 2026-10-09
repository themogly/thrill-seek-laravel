<?php

namespace App\Filament\Resources\Instructors\Schemas;

use App\Support\AdminImages;
use App\Support\ImageCrop;
use Filament\Forms\Components\CheckboxList;
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
                    ->helperText('Shown on the Meet the Team page — a paragraph or two is fine here; the homepage only shows their photo and name.')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                CheckboxList::make('disciplines')
                    ->label('Disciplines')
                    ->helperText('Which disciplines they teach. Tick all that apply — they show as tags on the Meet the Team page.')
                    ->relationship('disciplines', 'name')
                    ->columns(3)
                    ->columnSpanFull(),
                ImageCrop::ratio(
                    AdminImages::upload('photo')
                        ->label('Photo')
                        ->helperText('Optional, shown as a square portrait — crop to 1:1. Leave blank to show the initial-letter badge instead.')
                        ->disk('public')
                        ->directory('instructors')
                        ->columnSpanFull(),
                    '1:1',
                ),
            ]);
    }
}
