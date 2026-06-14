<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Defined once so every transactional email closes identically — the
        // shared mail layout renders it; no template repeats it. Newline kept so
        // "Blue skies," and the team line sit on separate lines.
        $this->migrator->add('general.email_signoff', "Blue skies,\nThe G-Force team");
    }
};
