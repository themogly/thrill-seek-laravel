<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // The Facebook-posts block is replaced by the News system. The Facebook
        // *profile* link (general.facebook_url) is a separate social link and stays.
        $this->migrator->deleteIfExists('home.facebook_posts');
        $this->migrator->deleteIfExists('home.facebook_caption');
    }

    public function down(): void
    {
        $this->migrator->add('home.facebook_caption', 'See our latest news and jump days.');
        $this->migrator->add('home.facebook_posts', []);
    }
};
