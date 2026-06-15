<?php

namespace App\Filament\Resources\Disciplines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DisciplineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->helperText('Shown as a tag on instructor cards, e.g. “Tandem”, “AFF”, “Coaching”.')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        // Auto-fill the slug from the name on create; never clobber an
                        // existing slug on edit (instructors are linked by it).
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Used in links to filter instructors by discipline. Lowercase, no spaces.')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rule('alpha_dash'),
            ]);
    }
}
