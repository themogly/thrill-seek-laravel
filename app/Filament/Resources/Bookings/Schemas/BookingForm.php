<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\TandemDate;
use App\Support\AdminDates;
use App\Support\MoneyField;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                        // "Awaiting payment" is the Stripe webhook's: it confirms or releases a
                        // held place only while the booking is still in it. Never offered by
                        // hand, and a held booking's status is locked until the webhook acts.
                        Select::make('status')
                            ->label('Status')
                            ->options(fn (?Booking $record): array => collect(BookingStatus::cases())
                                ->reject(fn (BookingStatus $status): bool => $status === BookingStatus::PendingPayment
                                    && $record?->status !== BookingStatus::PendingPayment)
                                ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => $status->getLabel()])
                                ->all())
                            ->disabled(fn (?Booking $record): bool => $record?->status === BookingStatus::PendingPayment)
                            ->helperText(fn (?Booking $record): ?string => $record?->status === BookingStatus::PendingPayment
                                ? 'Held while the customer pays online — this updates by itself when the payment completes or expires.'
                                : null)
                            ->default(BookingStatus::PendingDate->value)
                            ->live()
                            ->required(),
                        Select::make('tandem_date_id')
                            ->label('Jump slot')
                            ->helperText('Picking a slot sets the date below and confirms the booking.')
                            ->options(fn (): array => TandemDate::upcoming()
                                ->with('location')
                                ->get()
                                ->mapWithKeys(fn (TandemDate $slot): array => [
                                    // Location first so otherwise-identical dates at different
                                    // dropzones are distinguishable.
                                    $slot->id => $slot->location->name.' · '.$slot->starts_at->format('D j M Y, H:i')." ({$slot->remaining_capacity} of {$slot->capacity} places left)",
                                ])
                                ->all())
                            ->placeholder('No slot — set a date manually or leave pending')
                            ->live(),
                        // Create only: a booking the admin creates already confirmed (or confirmed by
                        // picking a slot) emails the customer the same confirmation the online path
                        // does — unless switched off, e.g. when entering an old booking by hand.
                        Toggle::make('notify_customer')
                            ->label('Email the customer')
                            ->helperText('Sends the booking confirmation, as an online booking does. Switch off when entering a past booking.')
                            ->default(true)
                            ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                && (self::isConfirmed($get('status')) || filled($get('tandem_date_id')))),
                        AdminDates::dateTime('scheduled_at')
                            ->label('Date & time'),
                        MoneyField::pounds('price_pence')
                            ->label('Price')
                            ->helperText('In pounds, e.g. 260.00.')
                            ->required(),
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

    private static function isConfirmed(mixed $status): bool
    {
        return ($status instanceof BookingStatus ? $status : BookingStatus::tryFrom((string) $status)) === BookingStatus::Confirmed;
    }
}
