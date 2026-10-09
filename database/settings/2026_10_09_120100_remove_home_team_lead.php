<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Remove home.team_lead — the lead line above the homepage "Meet the team" link,
 * dropped from the page in b494dd6 but still editable (CMS field-usage gate,
 * prompt 012). Same pattern as 2026_06_16_120000_remove_orphaned_cms_settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->delete('home.team_lead');
    }

    public function down(): void
    {
        $this->migrator->add('home.team_lead', "The people you'll fly with.");
    }
};
