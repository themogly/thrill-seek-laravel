<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\EmailTemplate;

/**
 * Renders admin-editable email templates by substituting {{ placeholder }}
 * tokens. Unknown tokens are left intact so a typo is visible in the email
 * rather than silently swallowed.
 */
final class TemplateRenderer
{
    /**
     * @param  array<string, string>  $variables
     * @return array{subject: string, body: string}
     */
    public static function render(EmailTemplate $template, array $variables): array
    {
        return [
            'subject' => self::substitute($template->subject, $variables),
            'body' => self::substitute($template->body, $variables),
        ];
    }

    /**
     * @param  array<string, string>  $variables
     */
    private static function substitute(string $text, array $variables): string
    {
        return (string) preg_replace_callback(
            '/{{\s*([\w.]+)\s*}}/',
            fn (array $matches): string => $variables[$matches[1]] ?? $matches[0],
            $text,
        );
    }
}
