<?php

declare(strict_types=1);

namespace App\Filament\Resources\TandemDates\Pages;

use App\Filament\Resources\TandemDates\TandemDateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTandemDate extends EditRecord
{
    protected static string $resource = TandemDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
