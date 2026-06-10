<?php

declare(strict_types=1);

namespace App\Filament\Resources\Bookings\Tables;

use App\Actions\RescheduleBooking;
use App\Enums\BookingStatus;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Customer')
                    ->description(fn (Booking $record): string => $record->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('—'),
                TextColumn::make('scheduled_at')
                    ->label('Jump date')
                    ->dateTime('D j M Y, H:i')
                    ->placeholder('Awaiting date')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('formatted_balance_due')
                    ->label('Balance due')
                    ->color(fn (Booking $record): string => $record->hasOutstandingBalance() ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(BookingStatus::class),
                Filter::make('outstanding_balance')
                    ->label('Outstanding balance')
                    ->query(fn (Builder $query): Builder => $query->whereRaw(Booking::outstandingBalanceSql())),
            ])
            ->recordActions([
                Action::make('reschedule')
                    ->label('Reschedule')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Select::make('availability_slot_id')
                            ->label('New slot')
                            ->options(fn (): array => AvailabilitySlot::upcoming()
                                ->get()
                                ->mapWithKeys(fn (AvailabilitySlot $slot): array => [
                                    $slot->id => $slot->starts_at->format('D j M Y, H:i')." ({$slot->remaining_capacity} of {$slot->capacity} places left)",
                                ])
                                ->all())
                            ->placeholder('Pick a slot, or set a custom date below'),
                        DateTimePicker::make('scheduled_at')
                            ->label('Or a custom date & time')
                            ->seconds(false),
                        Toggle::make('notify')
                            ->label('Email the customer')
                            ->default(true),
                    ])
                    ->action(function (Booking $record, array $data, RescheduleBooking $reschedule): void {
                        $slot = filled($data['availability_slot_id'] ?? null)
                            ? AvailabilitySlot::find($data['availability_slot_id'])
                            : null;

                        $newTime = $slot ?? (filled($data['scheduled_at'] ?? null) ? Carbon::parse($data['scheduled_at']) : null);

                        if ($newTime === null) {
                            Notification::make()
                                ->danger()
                                ->title('Pick a slot or a custom date')
                                ->send();

                            return;
                        }

                        $reschedule->handle($record, $newTime, (bool) $data['notify']);

                        Notification::make()
                            ->success()
                            ->title('Booking rescheduled')
                            ->body($data['notify'] ? 'The customer has been emailed.' : 'No email sent.')
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
