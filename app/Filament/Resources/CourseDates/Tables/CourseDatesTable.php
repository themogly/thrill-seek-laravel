<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseDates\Tables;

use App\Enums\CourseDateStatus;
use App\Models\CourseDate;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseDatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on')
            ->columns([
                TextColumn::make('date_range_label')
                    ->label('Dates')
                    ->sortable(['starts_on']),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('formatted_price')
                    ->label('Price'),
                TextColumn::make('formatted_deposit')
                    ->label('Deposit')
                    ->placeholder('—'),
                TextColumn::make('enrolled')
                    ->label('Enrolled')
                    ->state(fn (CourseDate $record): string => $record->enrolledCount()." of {$record->capacity}")
                    ->badge()
                    ->color(fn (CourseDate $record): string => $record->remaining_places === 0 ? 'danger' : 'success'),
                TextColumn::make('display_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->preload(),
                SelectFilter::make('status')
                    ->options(CourseDateStatus::class),
                Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereDate('starts_on', '>=', now()->toDateString())),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
