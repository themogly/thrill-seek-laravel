<?php

declare(strict_types=1);

namespace App\Settings;

use App\Settings\Concerns\ResolvesImagePaths;
use Spatie\LaravelSettings\Settings;

class AffPageSettings extends Settings
{
    use ResolvesImagePaths;

    public string $seo_title;

    public string $seo_description;

    public string $hero_title;

    public string $hero_subtitle;

    public string $intro_eyebrow;

    public string $intro_title;

    public string $intro_lead;

    /** @phpstan-var array<int, string> */
    public array $bullets;

    public string $intro_image;

    public string $trust_eyebrow;

    public string $trust_title;

    public string $trust_body;

    public string $pricing_eyebrow;

    public string $pricing_title;

    public string $repeat_pricing_heading;

    public string $info_eyebrow;

    public string $info_title;

    public string $info_lead;

    /** @phpstan-var array<int, array{icon: string, title: string, body: string}> */
    public array $info_cards;

    public static function group(): string
    {
        return 'aff_page';
    }
}
