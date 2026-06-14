<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Copy for the simple pages: shop, testimonials, hall of fame, contact and
 * the legal pages. Pages with richer layouts have their own settings group.
 */
class SimplePagesSettings extends Settings
{
    public string $shop_seo_title;

    public string $shop_seo_description;

    public string $shop_hero_title;

    public string $shop_hero_subtitle;

    public string $testimonials_seo_title;

    public string $testimonials_seo_description;

    public string $testimonials_hero_title;

    public string $testimonials_hero_subtitle;

    public string $hall_of_fame_seo_title;

    public string $hall_of_fame_seo_description;

    public string $hall_of_fame_hero_title;

    public string $hall_of_fame_hero_subtitle;

    public string $contact_seo_title;

    public string $contact_seo_description;

    public string $contact_hero_title;

    public string $contact_hero_subtitle;

    public string $contact_form_heading;

    public string $contact_direct_heading;

    public string $contact_newsletter_heading;

    public string $contact_newsletter_text;

    public string $booking_tandem_seo_title;

    public string $booking_tandem_seo_description;

    public string $booking_tandem_hero_title;

    public string $booking_tandem_hero_subtitle;

    public string $booking_aff_seo_title;

    public string $booking_aff_seo_description;

    public string $booking_aff_hero_title;

    public string $booking_aff_hero_subtitle;

    public string $voucher_seo_title;

    public string $voucher_seo_description;

    public string $voucher_hero_title;

    public string $voucher_hero_subtitle;

    public string $voucher_intro;

    public string $privacy_title;

    public string $privacy_body;

    /** Auto-stamped (Y-m-d) whenever the privacy body is saved — drives the "Last updated" line. */
    public string $privacy_updated_at;

    public string $terms_title;

    public string $terms_body;

    public static function group(): string
    {
        return 'simple_pages';
    }
}
