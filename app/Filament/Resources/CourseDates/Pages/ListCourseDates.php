<?php

namespace App\Filament\Resources\CourseDates\Pages;

use App\Filament\Resources\CourseDates\CourseDateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourseDates extends ListRecords
{
    protected static string $resource = CourseDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
