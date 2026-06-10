<?php

declare(strict_types=1);

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Models\TandemDate;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->columns(3)
                    ->components([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->maxLength(50),
                    ]),
                Section::make('Booking')
                    ->columns(2)
                    ->components([
                        Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->preload()
                            ->searchable(),
                        Select::make('status')
                            ->label('Status')
                            ->options(BookingStatus::class)
                            ->default(BookingStatus::PendingDate->value)
                            ->required(),
                        Select::make('tandem_date_id')
                            ->label('Jump slot')
                            ->helperText('Picking a slot sets the date below and confirms the booking.')
                            ->options(fn (): array => TandemDate::upcoming()
                                ->get()
                                ->mapWithKeys(fn (TandemDate $slot): array => [
                                    $slot->id => $slot->starts_at->format('D j M Y, H:i')." ({$slot->remaining_capacity} of {$slot->capacity} places left)",
                                ])
                                ->all())
                            ->placeholder('No slot — set a date manually or leave pending'),
                        DateTimePicker::make('scheduled_at')
                            ->label('Date & time')
                            ->seconds(false),
                        TextInput::make('price_pence')
                            ->label('Price (pence)')
                            ->helperText('e.g. 26000 = £260.')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ]),
                Section::make('Jump details')
                    ->components([
                        KeyValue::make('customer_details')
                            ->label('Customer declaration (weight, height, medical)')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->addActionLabel('Add detail'),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ]),
            ]);
    }
}
