<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShopItems\Pages;

use App\Filament\Resources\ShopItems\ShopItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShopItem extends CreateRecord
{
    protected static string $resource = ShopItemResource::class;
}
