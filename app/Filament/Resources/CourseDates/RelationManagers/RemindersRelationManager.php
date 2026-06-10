<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseDates\RelationManagers;

use App\Models\CourseReminder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';

    protected static ?string $title = 'Scheduled reminders';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('days_before')
                ->label('Days before the course starts')
                ->numeric()
                ->minValue(0)
                ->maxValue(365)
                ->required(),
            TextInput::make('subject')
                ->label('Subject')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label('Message')
                ->helperText('Sent to everyone on the course. Blank lines start a new paragraph.')
                ->rows(8)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('days_before', 'desc')
            ->columns([
                TextColumn::make('days_before')
                    ->label('When')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'On the day' : "{$state} days before"),
                TextColumn::make('subject')
                    ->label('Subject')
                    ->limit(60),
                TextColumn::make('sent_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?Carbon $state): string => $state === null ? 'Scheduled' : 'Sent '.$state->diffForHumans())
                    ->color(fn (?Carbon $state): string => $state === null ? 'warning' : 'success')
                    ->placeholder('Scheduled'),
            ])
            ->headerActions([
                CreateAction::make()->label('Schedule reminder'),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (CourseReminder $record): bool => $record->sent_at === null),
                DeleteAction::make()->visible(fn (CourseReminder $record): bool => $record->sent_at === null),
            ])
            ->toolbarActions([]);
    }
}
