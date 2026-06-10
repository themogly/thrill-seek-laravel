<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('aff_page.repeat_pricing_heading', 'Repeat jump pricing');
    }
};
