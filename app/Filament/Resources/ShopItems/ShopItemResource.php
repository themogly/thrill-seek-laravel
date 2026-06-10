<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShopItems;

use App\Filament\Resources\ShopItems\Pages\CreateShopItem;
use App\Filament\Resources\ShopItems\Pages\EditShopItem;
use App\Filament\Resources\ShopItems\Pages\ListShopItems;
use App\Filament\Resources\ShopItems\Schemas\ShopItemForm;
use App\Filament\Resources\ShopItems\Tables\ShopItemsTable;
use App\Models\ShopItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShopItemResource extends Resource
{
    protected static ?string $model = ShopItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Site content';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ShopItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShopItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShopItems::route('/'),
            'create' => CreateShopItem::route('/create'),
            'edit' => EditShopItem::route('/{record}/edit'),
        ];
    }
}
