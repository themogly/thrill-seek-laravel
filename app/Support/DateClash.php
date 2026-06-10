<?php

namespace App\Support;

use App\Enums\CourseDateStatus;
use App\Models\CourseDate;
use App\Models\TandemDate;
use Illuminate\Support\Carbon;

/**
 * Tandem dates and AFF courses are operationally exclusive: a tandem date
 * can never fall on a day inside an AFF course's range at the same
 * location, and vice versa. Both admin forms validate through this class
 * so create and edit enforce the same rule.
 */
final class DateClash
{
    /** The non-cancelled course whose range covers the given day, if any. */
    public static function courseCoveringDay(Carbon $day, int $locationId, ?int $ignoreCourseId = null): ?CourseDate
    {
        return CourseDate::query()
            ->where('location_id', $locationId)
            ->where('status', '!=', CourseDateStatus::Cancelled)
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->when($ignoreCourseId !== null, fn ($query) => $query->whereKeyNot($ignoreCourseId))
            ->first();
    }

    /** The tandem date that falls inside the given course range, if any. */
    public static function tandemDateWithinRange(Carbon $start, Carbon $end, int $locationId, ?int $ignoreTandemDateId = null): ?TandemDate
    {
        return TandemDate::query()
            ->where('location_id', $locationId)
            ->whereDate('starts_at', '>=', $start->toDateString())
            ->whereDate('starts_at', '<=', $end->toDateString())
            ->when($ignoreTandemDateId !== null, fn ($query) => $query->whereKeyNot($ignoreTandemDateId))
            ->first();
    }

    public static function describeCourse(CourseDate $course): string
    {
        return "Clashes with AFF course '{$course->product->name}' ({$course->date_range_label}) at this location.";
    }

    public static function describeTandemDate(TandemDate $tandemDate): string
    {
        return "Clashes with the tandem date on {$tandemDate->starts_at->format('j F Y')} at this location.";
    }
}
