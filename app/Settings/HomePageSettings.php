<?php

namespace App\Settings;

use App\Settings\Concerns\ResolvesImagePaths;
use Spatie\LaravelSettings\Settings;

class HomePageSettings extends Settings
{
    use ResolvesImagePaths;

    /** The homepage title/description hardcoded until 013 — seeded and used as the fallback. */
    public const DEFAULT_SEO_TITLE = 'G-Force Skydiving — One Life. One Adventure. Live It.';

    public const DEFAULT_SEO_DESCRIPTION = 'UK-based skydiving school offering tandem jumps, AFF courses and advanced coaching. Book your jump today.';

    public string $hero_eyebrow;

    public string $hero_title_1;

    public string $hero_title_highlight;

    public string $hero_title_2;

    public string $hero_subtitle;

    public string $hero_image;

    public string $hero_cta_primary_label;

    public string $hero_cta_secondary_label;

    public string $services_eyebrow;

    public string $services_title;

    public string $services_lead;

    public string $about_eyebrow;

    public string $about_title;

    public string $about_body;

    public string $about_image_1;

    public string $about_image_2;

    public string $trust_eyebrow;

    public string $trust_title;

    public string $instagram_caption;

    public string $testimonials_eyebrow;

    public string $testimonials_title;

    public string $newsletter_title;

    public string $newsletter_subtitle;

    public string $cta_title;

    public string $cta_subtitle;

    public string $cta_button_label;

    public string $seo_title;

    public string $seo_description;

    public static function group(): string
    {
        return 'home';
    }

    /**
     * The homepage <title>, with a safe default. Read defensively (not via the
     * raw property) so a stale settings cache that predates `seo_title` renders
     * today's title instead of throwing "must not be accessed before
     * initialization" on the most important page. Same rule as
     * GeneralSettings::emailSignoff().
     */
    public function seoTitle(): string
    {
        return isset($this->seo_title) && $this->seo_title !== ''
            ? $this->seo_title
            : self::DEFAULT_SEO_TITLE;
    }

    /** The homepage meta description, with the same defensive default. */
    public function seoDescription(): string
    {
        return isset($this->seo_description) && $this->seo_description !== ''
            ? $this->seo_description
            : self::DEFAULT_SEO_DESCRIPTION;
    }
}
