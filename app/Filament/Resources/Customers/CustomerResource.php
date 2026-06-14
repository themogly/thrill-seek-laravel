<?php

namespace App\Filament\Resources\Customers;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Customer;
use App\Models\Enquiry;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                            TextEntry::make('reference')
                                ->hiddenLabel()
                                ->weight('bold')
                                ->url(fn (Enquiry $record): string => EnquiryResource::getUrl('view', ['record' => $record])),
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
            // Surface who's waiting on a reply: unread-enquiry count + latest reply time.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount(['enquiries as unread_enquiries_count' => fn (Builder $q): Builder => $q->whereNull('read_at')])
                ->withMax('enquiries', 'last_customer_message_at'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('new_message')
                    ->label('')
                    ->icon(fn (Customer $record): ?string => ($record->unread_enquiries_count ?? 0) > 0 ? 'heroicon-s-bell-alert' : null)
                    ->color('danger')
                    ->tooltip('Has an unanswered message'),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('unread_enquiries_count')
                    ->label('Unread')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings'),
                TextColumn::make('enquiries_count')
                    ->label('Enquiries')
                    ->counts('enquiries'),
                TextColumn::make('enquiries_max_last_customer_message_at')
                    ->label('Last reply')
                    ->since()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('has_unread')
                    ->label('Has unread')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('enquiries', fn (Builder $q): Builder => $q->whereNull('read_at')),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('enquiries', fn (Builder $q): Builder => $q->whereNull('read_at')),
                    ),
                Filter::make('awaiting_reply')
                    ->label('Awaiting our reply')
                    ->query(fn (Builder $query): Builder => $query->whereHas('enquiries', fn (Builder $q): Builder => $q->whereIn('status', [
                        EnquiryStatus::New->value,
                        EnquiryStatus::CustomerReplied->value,
                    ]))),
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
