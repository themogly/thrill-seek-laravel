<?php

declare(strict_types=1);

namespace App\Filament\Resources\AvailabilitySlots\Pages;

use App\Filament\Resources\AvailabilitySlots\AvailabilitySlotResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAvailabilitySlot extends CreateRecord
{
    protected static string $resource = AvailabilitySlotResource::class;
}
