<?php

namespace App\Models;

use App\Enums\MessageDirection;
use Database\Factories\EnquiryMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message in an enquiry's conversation thread. Inbound messages come
 * from the public forms; outbound messages are admin replies sent by email.
 *
 * @property MessageDirection $direction
 */
class EnquiryMessage extends Model
{
    /** @use HasFactory<EnquiryMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
        'direction',
        'body',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
        ];
    }

    /** @return BelongsTo<Enquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
