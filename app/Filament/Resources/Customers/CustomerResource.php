<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Phone')
                ->maxLength(50),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact')
                ->columns(3)
                ->components([
                    TextEntry::make('name')->label('Name'),
                    TextEntry::make('email')->label('Email')->copyable(),
                    TextEntry::make('phone')->label('Phone')->placeholder('—'),
                ]),
            Section::make('Bookings')
                ->components([
                    TextEntry::make('total_spent')->label('Total paid'),
                    RepeatableEntry::make('bookings')
                        ->hiddenLabel()
                        ->placeholder('No bookings yet.')
                        ->components([
                            TextEntry::make('reference')->hiddenLabel(),
                            TextEntry::make('product.name')->hiddenLabel()->placeholder('—'),
                            TextEntry::make('status')->hiddenLabel()->badge(),
                            TextEntry::make('scheduled_at')->hiddenLabel()->dateTime()->placeholder('Awaiting date'),
                            TextEntry::make('formatted_balance_due')->hiddenLabel()->prefix('Due: '),
                        ])
                        ->columns(5),
                ]),
            Section::make('Enquiries')
                ->components([
                    RepeatableEntry::make('enquiries')
                        ->hiddenLabel()
                        ->placeholder('No enquiries yet.')
                        ->components([
                            TextEntry::make('reference')->hiddenLabel(),
                            TextEntry::make('product.name')->hiddenLabel()->placeholder('General'),
                            TextEntry::make('status')->hiddenLabel()->badge(),
                            TextEntry::make('created_at')->hiddenLabel()->since(),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings'),
                TextColumn::make('enquiries_count')
                    ->label('Enquiries')
                    ->counts('enquiries'),
                TextColumn::make('created_at')
                    ->label('First seen')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
