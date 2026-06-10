<?php

declare(strict_types=1);

namespace App\Settings;

use App\Settings\Concerns\ResolvesImagePaths;
use Spatie\LaravelSettings\Settings;

class HomePageSettings extends Settings
{
    use ResolvesImagePaths;

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

    /**
     * No `@var` tag: spatie's docblock reflector can't parse array shapes,
     * and an absent tag means "no cast", which is correct for plain arrays.
     *
     * @phpstan-var array<int, array{icon: string, value: string, label: string}>
     */
    public array $about_stats;

    public string $about_image_1;

    public string $about_image_2;

    public string $trust_eyebrow;

    public string $trust_title;

    public string $team_eyebrow;

    public string $team_title;

    public string $team_lead;

    public string $instagram_caption;

    public string $instagram_note;

    public string $facebook_caption;

    /** @phpstan-var array<int, array{title: string, description: string}> */
    public array $facebook_posts;

    public string $testimonials_eyebrow;

    public string $testimonials_title;

    public string $newsletter_title;

    public string $newsletter_subtitle;

    public string $cta_title;

    public string $cta_subtitle;

    public string $cta_button_label;

    public static function group(): string
    {
        return 'home';
    }
}
