<?php

namespace App\Filament\Resources\TandemDates\Pages;

use App\Filament\Resources\TandemDates\TandemDateResource;
use App\Support\AdminActions;
use Filament\Resources\Pages\EditRecord;

class EditTandemDate extends EditRecord
{
    protected static string $resource = TandemDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdminActions::guardedDelete(),
        ];
    }
}
