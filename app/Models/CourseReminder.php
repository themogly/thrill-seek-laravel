<?php

namespace App\Models;

use Database\Factories\CourseReminderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A scheduled email to everyone on a course, sent once when the course is
 * `days_before` days (or fewer) from starting. `sent_at` is the idempotency
 * marker — the scheduler can tick as often as it likes.
 *
 * @property Carbon|null $sent_at
 */
class CourseReminder extends Model
{
    /** @use HasFactory<CourseReminderFactory> */
    use HasFactory;

    protected $fillable = [
        'course_date_id',
        'days_before',
        'subject',
        'body',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CourseDate, $this> */
    public function courseDate(): BelongsTo
    {
        return $this->belongsTo(CourseDate::class);
    }

    public function isDue(): bool
    {
        return $this->sent_at === null
            && $this->courseDate !== null
            && $this->courseDate->start_date->isFuture()
            && now()->toDateString() >= $this->courseDate->start_date->copy()->subDays($this->days_before)->toDateString();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->whereNull('sent_at');
    }
}
