<?php

declare(strict_types=1);

namespace App\Filament\Resources\HallOfFame\Pages;

use App\Filament\Resources\HallOfFame\HallOfFameEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHallOfFameEntry extends CreateRecord
{
    protected static string $resource = HallOfFameEntryResource::class;
}
