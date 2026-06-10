<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A reusable file in the document library (medical form, training manual,
 * kit list…) that can be attached to any course message. Stored on the
 * local (non-public) disk under documents/.
 */
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /** Per-file upload cap. Resend rejects messages over ~40MB encoded. */
    public const MAX_FILE_BYTES = 10 * 1024 * 1024;

    /** Combined attachment cap per message, leaving headroom for encoding. */
    public const MAX_MESSAGE_ATTACHMENT_BYTES = 15 * 1024 * 1024;

    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ];

    protected $fillable = [
        'name',
        'file_path',
        'original_filename',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsToMany<CourseMessage, $this> */
    public function courseMessages(): BelongsToMany
    {
        return $this->belongsToMany(CourseMessage::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedSize(): Attribute
    {
        return Attribute::make(get: function (): string {
            $mb = $this->size_bytes / (1024 * 1024);

            return $mb >= 1
                ? number_format($mb, 1).' MB'
                : number_format($this->size_bytes / 1024).' KB';
        });
    }
}
