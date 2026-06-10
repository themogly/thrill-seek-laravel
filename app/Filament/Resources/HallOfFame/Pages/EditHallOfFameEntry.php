<?php

declare(strict_types=1);

namespace App\Filament\Resources\HallOfFame\Pages;

use App\Filament\Resources\HallOfFame\HallOfFameEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHallOfFameEntry extends EditRecord
{
    protected static string $resource = HallOfFameEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
