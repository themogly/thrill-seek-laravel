<?php

declare(strict_types=1);

namespace App\Settings;

use App\Settings\Concerns\ResolvesImagePaths;
use Spatie\LaravelSettings\Settings;

class CoachedPageSettings extends Settings
{
    use ResolvesImagePaths;

    public string $seo_title;

    public string $seo_description;

    public string $hero_title;

    public string $hero_subtitle;

    public string $hero_image;

    public string $price_eyebrow;

    public string $heading;

    public string $body;

    /** @phpstan-var array<int, string> */
    public array $skills;

    public string $image;

    public string $button_label;

    public static function group(): string
    {
        return 'coached_page';
    }
}
