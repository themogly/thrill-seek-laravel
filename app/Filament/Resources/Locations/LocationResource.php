<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Models\Location;
use App\Support\AdminActions;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Location')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, callable $set, ?string $state): void {
                            if (blank($get('slug')) && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Reference (slug)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->alphaDash(),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpanFull(),
                    FileUpload::make('image')
                        ->label('Photo')
                        ->helperText('Leave empty to keep the current image.')
                        ->image()
                        ->disk('public')
                        ->directory('locations')
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    Toggle::make('active')
                        ->label('Active')
                        ->helperText('Inactive locations are hidden from new dates and courses.')
                        ->default(true)
                        ->inline(false),
                ]),
            Section::make('Address')
                ->columns(2)
                ->components([
                    TextInput::make('address_line')->label('Address')->maxLength(255),
                    TextInput::make('town')->label('Town')->maxLength(255),
                    TextInput::make('region')->label('Region / county')->maxLength(255),
                    TextInput::make('postcode')->label('Postcode')->maxLength(20),
                    TextInput::make('country')->label('Country')->required()->maxLength(255),
                    TextInput::make('lat')->label('Latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('lng')->label('Longitude')->numeric()->minValue(-180)->maxValue(180),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region')
                    ->label('Region')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('country')
                    ->label('Country'),
                TextColumn::make('tandem_dates_count')
                    ->label('Tandem dates')
                    ->counts('tandemDates'),
                TextColumn::make('course_dates_count')
                    ->label('Courses')
                    ->counts('courseDates'),
                IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('active'),
            ])
            ->recordActions([
                EditAction::make(),
                AdminActions::guardedDelete(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
