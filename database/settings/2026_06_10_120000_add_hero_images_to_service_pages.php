<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tandem_page.hero_image', '/images/tandem.jpg');
        $this->migrator->add('aff_page.hero_image', '/images/aff.jpg');
        $this->migrator->add('coached_page.hero_image', '/images/coached.jpg');
    }
};
