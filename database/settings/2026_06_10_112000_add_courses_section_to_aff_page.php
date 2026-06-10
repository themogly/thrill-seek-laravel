<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('aff_page.courses_eyebrow', 'Upcoming courses');
        $this->migrator->add('aff_page.courses_title', 'Pick your week in the sun');
        $this->migrator->add('aff_page.courses_lead', 'Small groups, big progress — every course runs with limited places for personal coaching.');
        $this->migrator->add('aff_page.courses_empty_text', "New course dates are being finalised — send an enquiry and you'll be first to know.");
    }
};
