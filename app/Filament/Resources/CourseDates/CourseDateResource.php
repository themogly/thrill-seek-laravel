<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseDates;

use App\Filament\Resources\CourseDates\Pages\CreateCourseDate;
use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Filament\Resources\CourseDates\Pages\ListCourseDates;
use App\Filament\Resources\CourseDates\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\CourseDates\Schemas\CourseDateForm;
use App\Filament\Resources\CourseDates\Tables\CourseDatesTable;
use App\Models\CourseDate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CourseDateResource extends Resource
{
    protected static ?string $model = CourseDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'course date';

    protected static ?string $pluralModelLabel = 'AFF courses';

    public static function form(Schema $schema): Schema
    {
        return CourseDateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseDatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseDates::route('/'),
            'create' => CreateCourseDate::route('/create'),
            'edit' => EditCourseDate::route('/{record}/edit'),
        ];
    }
}
