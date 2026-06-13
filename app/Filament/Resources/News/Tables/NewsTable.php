<?php

namespace App\Filament\Resources\News\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(60),
                IconColumn::make('published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Publish date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('courseDate.date_range_label')
                    ->label('Linked course')
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('published')->label('Published'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
