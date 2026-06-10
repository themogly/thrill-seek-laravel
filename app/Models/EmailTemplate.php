<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin-editable email template. Placeholders like {{ name }} are
 * replaced at send time; see TemplateRenderer for the available variables
 * per template key.
 *
 * @property array<int, string>|null $variables
 */
class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'variables',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }

    public static function findByKey(string $key): self
    {
        return self::where('key', $key)->firstOrFail();
    }
}
