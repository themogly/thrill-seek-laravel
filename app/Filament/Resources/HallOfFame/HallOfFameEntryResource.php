<?php

namespace App\Filament\Resources\HallOfFame;

use App\Filament\Resources\HallOfFame\Pages\CreateHallOfFameEntry;
use App\Filament\Resources\HallOfFame\Pages\EditHallOfFameEntry;
use App\Filament\Resources\HallOfFame\Pages\ListHallOfFameEntries;
use App\Filament\Resources\HallOfFame\Schemas\HallOfFameEntryForm;
use App\Filament\Resources\HallOfFame\Tables\HallOfFameEntriesTable;
use App\Models\HallOfFameEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HallOfFameEntryResource extends Resource
{
    protected static ?string $model = HallOfFameEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Site content';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'hall of fame entry';

    protected static ?string $pluralModelLabel = 'hall of fame';

    public static function form(Schema $schema): Schema
    {
        return HallOfFameEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HallOfFameEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHallOfFameEntries::route('/'),
            'create' => CreateHallOfFameEntry::route('/create'),
            'edit' => EditHallOfFameEntry::route('/{record}/edit'),
        ];
    }
}
