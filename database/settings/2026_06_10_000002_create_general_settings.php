<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'G-Force Skydiving');
        $this->migrator->add('general.tagline', 'One Life. One Adventure. Live It.');
        $this->migrator->add('general.phone', '+44 (0)7583 155 951');
        $this->migrator->add('general.email', 'info@gforceskydiving.co.uk');
        $this->migrator->add('general.instagram_url', 'https://instagram.com');
        $this->migrator->add('general.facebook_url', 'https://facebook.com');
        $this->migrator->add('general.instagram_handle', '@gforceskydiving');
        $this->migrator->add('general.seo_title', 'G-Force Skydiving — One Life. One Adventure. Live It.');
        $this->migrator->add('general.seo_description', 'Tandem skydives, AFF courses and advanced coaching in the UK and Spain. Military-trained, BS & USPA certified instructors.');
        $this->migrator->add('general.og_image', 'https://pub-bb2e103a32db4e198524a2e9ed8f35b4.r2.dev/390eabd9-2dea-4686-bffb-bbf2b761eef3/id-preview-c55e11e8--05e69f77-3e5f-46a1-8301-364b0e237a36.lovable.app-1779138654806.png');
        $this->migrator->add('general.footer_copyright', '©2026 G-Force Skydiving. All rights reserved.');
    }
};
