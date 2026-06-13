<?php

use Illuminate\Support\Str;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // The seeded og:image was an external build-tool preview URL (a
        // placeholder). Default it to a real bundled site image so social
        // shares work; the owner can replace it with a branded 1200x630 image.
        $this->migrator->update('general.og_image', function (mixed $current): string {
            return Str::contains((string) $current, ['lovable', 'r2.dev', 'id-preview'])
                ? '/images/hero-skydive.jpg'
                : (string) $current;
        });
    }

    public function down(): void
    {
        // No-op: we won't restore a placeholder URL.
    }
};
