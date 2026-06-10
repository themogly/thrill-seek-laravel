<?php

namespace App\Filament\Resources\TandemDates;

use App\Filament\Resources\TandemDates\Pages\CreateTandemDate;
use App\Filament\Resources\TandemDates\Pages\EditTandemDate;
use App\Filament\Resources\TandemDates\Pages\ListTandemDates;
use App\Models\TandemDate;
use App\Support\DateClash;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class TandemDateResource extends Resource
{
    protected static ?string $model = TandemDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'tandem date';

    protected static ?string $pluralModelLabel = 'tandem dates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('location_id')
                ->label('Location')
                ->relationship('location', 'name', fn ($query) => $query->where('active', true))
                ->searchable()
                ->preload()
                ->required(),
            DateTimePicker::make('starts_at')
                ->label('Date & time')
                ->seconds(false)
                ->required()
                ->rule(fn (Get $get, ?TandemDate $record) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                    $locationId = $get('location_id');

                    if (blank($value) || blank($locationId)) {
                        return;
                    }

                    $clash = DateClash::courseCoveringDay(Carbon::parse($value), (int) $locationId);

                    if ($clash !== null) {
                        $fail(DateClash::describeCourse($clash));
                    }
                }),
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
                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Date & time')
                    ->dateTime('D j M Y, H:i')
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Capacity'),
                TextColumn::make('remaining_capacity')
                    ->label('Places left')
                    ->badge()
                    ->color(fn (TandemDate $record): string => $record->isFull() ? 'danger' : 'success'),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->preload(),
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
            'index' => ListTandemDates::route('/'),
            'create' => CreateTandemDate::route('/create'),
            'edit' => EditTandemDate::route('/{record}/edit'),
        ];
    }
}
