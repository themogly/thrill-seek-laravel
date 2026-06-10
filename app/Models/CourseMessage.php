<?php

namespace App\Models;

use Database\Factories\CourseMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The audit record of one outbound message to everyone on a course: who it
 * went to (snapshot at send time), when, what, and with which attachments.
 *
 * @property array<int, array{booking_id: int, name: string, email: string}> $recipients
 */
class CourseMessage extends Model
{
    /** @use HasFactory<CourseMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'course_date_id',
        'user_id',
        'subject',
        'body',
        'recipients',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
        ];
    }

    /** @return BelongsTo<CourseDate, $this> */
    public function courseDate(): BelongsTo
    {
        return $this->belongsTo(CourseDate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Document, $this> */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }

    public function recipientCount(): int
    {
        return count($this->recipients ?? []);
    }
}
