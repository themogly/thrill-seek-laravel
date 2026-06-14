<?php

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

    /** Sign-off rendered once at the foot of every transactional email. */
    public string $email_signoff;

    /** @phpstan-var array<int, array{icon: string, value: string, label: string}> */
    public array $trust_items;

    /** Storefront switch — off hides the shop everywhere and 404s its routes. */
    public bool $shop_enabled;

    /** Public checkout switch — off reverts the site to enquiry-first (admin
     *  payment tools and the Stripe webhook are unaffected). */
    public bool $online_payments_enabled;

    /** News switch — off hides News from the nav/footer/home and 404s its routes. */
    public bool $news_enabled;

    public static function group(): string
    {
        return 'general';
    }

    /** "tel:" href derived from the display phone number. */
    public function phoneHref(): string
    {
        return 'tel:'.preg_replace('/[^+\d]/', '', $this->phone);
    }

    /**
     * The transactional-email sign-off, with a safe default. Read defensively
     * (not via the raw property) so a stale settings cache that predates the
     * `email_signoff` property degrades gracefully instead of throwing
     * "must not be accessed before initialization" and failing EVERY email job.
     * The proper fix for a stale cache is still `php artisan settings:clear-cache`.
     */
    public function emailSignoff(): string
    {
        return isset($this->email_signoff) && $this->email_signoff !== ''
            ? $this->email_signoff
            : "Blue skies,\nThe G-Force team";
    }
}
