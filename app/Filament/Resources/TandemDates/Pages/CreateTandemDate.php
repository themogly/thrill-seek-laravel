<?php

declare(strict_types=1);

namespace App\Filament\Resources\TandemDates\Pages;

use App\Filament\Resources\TandemDates\TandemDateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTandemDate extends CreateRecord
{
    protected static string $resource = TandemDateResource::class;
}
