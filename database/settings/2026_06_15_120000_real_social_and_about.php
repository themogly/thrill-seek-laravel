<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Real social profile URLs (were generic homepage placeholders). Updated
        // conditionally so an owner edit is never clobbered on re-run.
        $this->migrator->update(
            'general.instagram_url',
            fn (string $current): string => $current === 'https://instagram.com'
                ? 'https://www.instagram.com/gforceskydiving/'
                : $current,
        );
        $this->migrator->update(
            'general.facebook_url',
            fn (string $current): string => $current === 'https://facebook.com'
                ? 'https://www.facebook.com/Gforceskydiving.co.uk/'
                : $current,
        );

        // Factual fix: the founding team is mixed-background (one ex-military, one
        // ex-pro snowboarder/instructor), not "ex-military jumpers" plural. Keep the
        // slot's length/tone — accuracy only.
        $old = 'G-Force Skydiving was founded by ex-military jumpers with a passion for sharing the sport safely. With over 30 years of combined experience, our team holds both British Skydiving and USPA certifications, and operates across the UK and Europe.';
        $new = 'G-Force Skydiving was founded in 2017 by friends with a shared passion for sharing the sport safely. With over 30 years of combined experience, our team holds both British Skydiving and USPA certifications, and operates across the UK and Europe.';
        $this->migrator->update(
            'home.about_body',
            fn (string $current): string => $current === $old ? $new : $current,
        );
    }
};
