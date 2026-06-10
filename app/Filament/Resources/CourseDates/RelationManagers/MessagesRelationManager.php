<?php

namespace App\Filament\Resources\CourseDates\RelationManagers;

use App\Models\CourseMessage;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Message history';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message')
                ->components([
                    TextEntry::make('subject')->label('Subject'),
                    TextEntry::make('body')->label('Body'),
                    TextEntry::make('documents.name')
                        ->label('Attachments')
                        ->badge()
                        ->placeholder('None'),
                    TextEntry::make('created_at')->label('Sent')->dateTime(),
                    TextEntry::make('user.name')->label('Sent by')->placeholder('Automatic reminder'),
                ]),
            Section::make('Recipients')
                ->components([
                    RepeatableEntry::make('recipients')
                        ->hiddenLabel()
                        ->components([
                            TextEntry::make('name')->hiddenLabel(),
                            TextEntry::make('email')->hiddenLabel()->color('gray'),
                        ])
                        ->columns(2),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->label('Subject')
                    ->limit(50),
                TextColumn::make('source')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'reminder' ? 'Reminder' : 'Manual')
                    ->color(fn (string $state): string => $state === 'reminder' ? 'info' : 'primary'),
                TextColumn::make('recipients')
                    ->label('Recipients')
                    ->state(fn (CourseMessage $record): string => (string) $record->recipientCount()),
                TextColumn::make('documents_count')
                    ->label('Attachments')
                    ->counts('documents'),
                TextColumn::make('user.name')
                    ->label('Sent by')
                    ->placeholder('Automatic'),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
