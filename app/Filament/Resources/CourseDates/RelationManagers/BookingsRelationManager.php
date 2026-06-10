<?php

namespace App\Filament\Resources\CourseDates\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Customers on this course';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref'),
                TextColumn::make('name')
                    ->label('Customer')
                    ->description(fn (Booking $record): string => $record->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Booking')
                    ->badge(),
                TextColumn::make('payment_state')
                    ->label('Payment')
                    ->badge(),
                TextColumn::make('formatted_balance_due')
                    ->label('Balance due'),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('open')
                    ->label('Open booking')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Booking $record): string => BookingResource::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
