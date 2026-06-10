<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseDates\Pages;

use App\Filament\Resources\CourseDates\CourseDateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseDate extends EditRecord
{
    protected static string $resource = CourseDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
