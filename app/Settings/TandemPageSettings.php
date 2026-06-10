<?php

declare(strict_types=1);

namespace App\Settings;

use App\Settings\Concerns\ResolvesImagePaths;
use Spatie\LaravelSettings\Settings;

class TandemPageSettings extends Settings
{
    use ResolvesImagePaths;

    public string $seo_title;

    public string $seo_description;

    public string $og_title;

    public string $og_description;

    public string $hero_title;

    public string $hero_subtitle;

    public string $intro_eyebrow;

    public string $intro_title;

    public string $intro_lead;

    /** @phpstan-var array<int, string> */
    public array $bullets;

    public string $locations_heading;

    /** @phpstan-var array<int, string> */
    public array $locations;

    public string $intro_image;

    public string $pricing_eyebrow;

    public string $pricing_title;

    public string $charity_note_title;

    public string $charity_note_body;

    public static function group(): string
    {
        return 'tandem_page';
    }
}
