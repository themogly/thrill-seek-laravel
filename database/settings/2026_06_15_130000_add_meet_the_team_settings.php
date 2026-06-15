<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('simple_pages.meet_the_team_seo_title', 'Meet the Team — G-Force Skydiving');
        $this->migrator->add('simple_pages.meet_the_team_seo_description', 'The British Skydiving and USPA-rated instructors who will train you and jump with you at G-Force Skydiving.');
        $this->migrator->add('simple_pages.meet_the_team_hero_title', 'Meet the Team');
        $this->migrator->add('simple_pages.meet_the_team_hero_subtitle', 'The instructors who will brief you, train you and jump with you.');
        $this->migrator->add('simple_pages.home_team_teaser_line', 'Your jumps are run by British Skydiving and USPA-rated instructors.');
    }

    public function down(): void
    {
        $this->migrator->delete('simple_pages.meet_the_team_seo_title');
        $this->migrator->delete('simple_pages.meet_the_team_seo_description');
        $this->migrator->delete('simple_pages.meet_the_team_hero_title');
        $this->migrator->delete('simple_pages.meet_the_team_hero_subtitle');
        $this->migrator->delete('simple_pages.home_team_teaser_line');
    }
};
