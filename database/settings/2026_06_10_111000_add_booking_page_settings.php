<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('simple_pages.booking_tandem_seo_title', 'Book a Tandem Skydive — G-Force Skydiving');
        $this->migrator->add('simple_pages.booking_tandem_seo_description', 'Pick a date, tell us about you, pay securely — your 15,000ft tandem skydive booked in minutes.');
        $this->migrator->add('simple_pages.booking_tandem_hero_title', 'Book Your Jump');
        $this->migrator->add('simple_pages.booking_tandem_hero_subtitle', 'Pick a date, tell us about you, pay securely. Done in minutes — adrenaline guaranteed.');

        $this->migrator->add('simple_pages.booking_aff_seo_title', 'Book an AFF Course — G-Force Skydiving');
        $this->migrator->add('simple_pages.booking_aff_seo_description', 'Reserve your place on an AFF course in Spain with a deposit. Beginner to licensed skydiver in eight levels.');
        $this->migrator->add('simple_pages.booking_aff_hero_title', 'Reserve Your Course');
        $this->migrator->add('simple_pages.booking_aff_hero_subtitle', 'Pick a course, secure your place with a deposit, start your journey to a skydiving licence.');
    }
};
