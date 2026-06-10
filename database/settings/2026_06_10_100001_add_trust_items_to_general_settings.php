<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.trust_items', [
            ['icon' => 'shield-check', 'value' => 'Ex-Military', 'label' => 'Instructor backgrounds'],
            ['icon' => 'users', 'value' => '30+ Years', 'label' => 'Combined experience'],
            ['icon' => 'medal', 'value' => 'BS / USPA', 'label' => 'Certified instructors'],
            ['icon' => 'calendar-check', 'value' => 'Est. 2017', 'label' => 'Proven track record'],
        ]);
    }
};
