<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tandem_page.hero_image', '/images/tandem.webp');
        $this->migrator->add('aff_page.hero_image', '/images/aff.webp');
        $this->migrator->add('coached_page.hero_image', '/images/coached.webp');
    }
};
