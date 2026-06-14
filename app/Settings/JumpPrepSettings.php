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

    /**
     * The same pre-jump info as the account "before your jump" panel, formatted
     * as a plain-text block for the tandem confirmation/reminder emails — so the
     * owner edits it in ONE place and it stays in sync everywhere. The leading
     * blank lines let it drop into a templated email body via {{ jump_prep }}
     * without the surrounding template needing to know whether it's present.
     */
    public function emailBlock(): string
    {
        return "\n\nBefore your jump"
            ."\n\nArrival & timing: ".$this->arrival_info
            ."\n\nWhat to bring: ".$this->what_to_bring
            ."\n\nWhat to expect: ".$this->what_to_expect;
    }
}
