<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public string $tagline;

    public string $phone;

    public string $email;

    public string $instagram_url;

    public string $facebook_url;

    public string $instagram_handle;

    public string $seo_title;

    public string $seo_description;

    public string $og_image;

    public string $footer_copyright;

    public static function group(): string
    {
        return 'general';
    }

    /** "tel:" href derived from the display phone number. */
    public function phoneHref(): string
    {
        return 'tel:'.preg_replace('/[^+\d]/', '', $this->phone);
    }
}
