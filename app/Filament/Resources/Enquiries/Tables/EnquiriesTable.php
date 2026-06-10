<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Tables;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable()
                    ->weight(fn (Enquiry $record): string => $record->isUnread() ? 'bold' : 'normal'),
                TextColumn::make('name')
                    ->label('From')
                    ->description(fn (Enquiry $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->weight(fn (Enquiry $record): string => $record->isUnread() ? 'bold' : 'normal'),
                TextColumn::make('product.name')
                    ->label('About')
                    ->placeholder('General'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnquiryStatus::class),
                TernaryFilter::make('unread')
                    ->label('Unread')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('read_at'),
                        false: fn (Builder $query) => $query->whereNotNull('read_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
