<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Give the homepage its own SEO title/description (prompt 013). Seeded with
 * exactly the literals home.blade.php hardcoded until now, so the rendered
 * <head> is unchanged on deploy until the owner edits them.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('home.seo_title', 'G-Force Skydiving — One Life. One Adventure. Live It.');
        $this->migrator->add('home.seo_description', 'UK-based skydiving school offering tandem jumps, AFF courses and advanced coaching. Book your jump today.');
    }

    public function down(): void
    {
        $this->migrator->delete('home.seo_title');
        $this->migrator->delete('home.seo_description');
    }
};
