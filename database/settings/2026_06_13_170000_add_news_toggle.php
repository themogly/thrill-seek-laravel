<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // News is on by default — it replaces the old Facebook-posts block.
        $this->migrator->add('general.news_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.news_enabled');
    }
};
