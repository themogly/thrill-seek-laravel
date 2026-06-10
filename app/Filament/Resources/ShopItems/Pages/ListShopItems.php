<?php

namespace App\Filament\Resources\ShopItems\Pages;

use App\Filament\Resources\ShopItems\ShopItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopItems extends ListRecords
{
    protected static string $resource = ShopItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
