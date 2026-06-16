<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Remove five orphaned CMS settings (see CMS-FIELD-AUDIT.md) — editable in the
 * admin but rendered nowhere after the homepage/team UI rebuilds. Safe on existing
 * data: these keys are created by earlier migrations, so they always exist to delete.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->delete('home.about_stats');          // duplicate stat tiles (now only in trust_items)
        $this->migrator->delete('home.team_eyebrow');         // old home team-teaser eyebrow
        $this->migrator->delete('home.team_title');           // old team-teaser title ("The Coaches")
        $this->migrator->delete('home.instagram_note');       // leftover from the removed live Instagram feed
        $this->migrator->delete('simple_pages.home_team_teaser_line'); // superseded by home.team_lead
    }

    public function down(): void
    {
        $this->migrator->add('home.about_stats', [
            ['icon' => 'plane', 'value' => '15k ft', 'label' => 'Highest UK Tandem'],
            ['icon' => 'users', 'value' => '30+ yrs', 'label' => 'Combined Experience'],
            ['icon' => 'award', 'value' => 'BS / USPA', 'label' => 'Certified'],
        ]);
        $this->migrator->add('home.team_eyebrow', 'Meet the team');
        $this->migrator->add('home.team_title', 'The Coaches');
        $this->migrator->add('home.instagram_note', 'Live Instagram feed connects via Meta Graph API — ask to enable.');
        $this->migrator->add('simple_pages.home_team_teaser_line', 'Your jumps are run by British Skydiving and USPA-rated instructors.');
    }
};
