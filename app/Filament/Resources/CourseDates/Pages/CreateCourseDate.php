<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseDates\Pages;

use App\Filament\Resources\CourseDates\CourseDateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseDate extends CreateRecord
{
    protected static string $resource = CourseDateResource::class;
}
