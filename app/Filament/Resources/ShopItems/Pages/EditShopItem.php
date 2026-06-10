<?php

namespace App\Filament\Resources\ShopItems\Pages;

use App\Filament\Resources\ShopItems\ShopItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopItem extends EditRecord
{
    protected static string $resource = ShopItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
