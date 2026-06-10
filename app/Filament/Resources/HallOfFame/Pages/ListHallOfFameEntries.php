<?php

declare(strict_types=1);

namespace App\Filament\Resources\HallOfFame\Pages;

use App\Filament\Resources\HallOfFame\HallOfFameEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHallOfFameEntries extends ListRecords
{
    protected static string $resource = HallOfFameEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
