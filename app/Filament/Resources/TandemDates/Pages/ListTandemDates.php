<?php

namespace App\Filament\Resources\TandemDates\Pages;

use App\Filament\Resources\TandemDates\TandemDateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTandemDates extends ListRecords
{
    protected static string $resource = TandemDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
