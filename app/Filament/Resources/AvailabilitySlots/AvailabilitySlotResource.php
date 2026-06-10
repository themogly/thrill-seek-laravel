<?php

declare(strict_types=1);

namespace App\Filament\Resources\AvailabilitySlots;

use App\Filament\Resources\AvailabilitySlots\Pages\CreateAvailabilitySlot;
use App\Filament\Resources\AvailabilitySlots\Pages\EditAvailabilitySlot;
use App\Filament\Resources\AvailabilitySlots\Pages\ListAvailabilitySlots;
use App\Models\AvailabilitySlot;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AvailabilitySlotResource extends Resource
{
    protected static ?string $model = AvailabilitySlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?string $modelLabel = 'availability slot';

    protected static ?string $pluralModelLabel = 'availability';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('starts_at')
                ->label('Date & time')
                ->seconds(false)
                ->required(),
            TextInput::make('capacity')
                ->label('Capacity')
                ->helperText('How many jumpers this slot can take.')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('notes')
                ->label('Notes')
                ->helperText('Internal only, e.g. “Devon DZ” or “AFF course week”.')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Date & time')
                    ->dateTime('D j M Y, H:i')
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Capacity'),
                TextColumn::make('remaining_capacity')
                    ->label('Places left')
                    ->badge()
                    ->color(fn (AvailabilitySlot $record): string => $record->isFull() ? 'danger' : 'success'),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>=', now())),
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

    public static function getPages(): array
    {
        return [
            'index' => ListAvailabilitySlots::route('/'),
            'create' => CreateAvailabilitySlot::route('/create'),
            'edit' => EditAvailabilitySlot::route('/{record}/edit'),
        ];
    }
}
