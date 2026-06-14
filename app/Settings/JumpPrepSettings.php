<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Owner-editable "before your jump" info shown to a customer with an upcoming
 * booking (account dashboard + booking detail). Plain text the owner can keep
 * current — reduces pre-jump admin questions.
 */
class JumpPrepSettings extends Settings
{
    public string $arrival_info;

    public string $what_to_bring;

    public string $what_to_expect;

    public static function group(): string
    {
        return 'jump_prep';
    }
}
