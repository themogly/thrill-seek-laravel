<?php

namespace App\Filament\Resources\Enquiries\Tables;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product', 'latestMessage']))
            // Needs-attention first by DEFAULT: unread (incl. new customer replies) at the
            // top, then most-recent activity. A default sort, not a forced order, so
            // choosing a column sort takes over (prompt 025).
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw('read_at is null desc')
                ->orderByRaw('coalesce(last_customer_message_at, created_at) desc'))
            ->columns([
                IconColumn::make('unread')
                    ->label('')
                    ->icon(fn (Enquiry $record): ?string => $record->isUnread() ? 'heroicon-s-bell-alert' : null)
                    ->color('danger')
                    ->tooltip('Unread — needs a reply'),
                TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable()
                    ->weight(fn (Enquiry $record): string => $record->isUnread() ? 'bold' : 'normal'),
                TextColumn::make('name')
                    ->label('From')
                    ->description(fn (Enquiry $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->weight(fn (Enquiry $record): string => $record->isUnread() ? 'bold' : 'normal'),
                TextColumn::make('latestMessage.body')
                    ->label('Latest message')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state !== null && trim($state) !== '' ? Str::limit(trim($state), 60) : '—')
                    ->color('gray')
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('last_activity')
                    ->label('Last activity')
                    ->state(fn (Enquiry $record) => $record->last_customer_message_at ?? $record->created_at)
                    ->since()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw('coalesce(last_customer_message_at, created_at) '.$direction)),
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
