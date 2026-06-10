<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GalleryImage;
use App\Models\HallOfFameEntry;
use App\Models\Instructor;
use App\Models\Product;
use App\Settings\AffPageSettings;
use App\Settings\CoachedPageSettings;
use App\Settings\HomePageSettings;
use App\Settings\TandemPageSettings;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelSettings\Settings;

/**
 * The registry behind the image-optimisation pipeline: which upload
 * directories map to which maximum dimensions, and which model attributes /
 * settings properties hold image paths (so a converted image's references
 * can be rewritten to the .webp path).
 */
final class ImageOptimization
{
    /** Default longest-edge limit when a directory has no specific rule. */
    public const DEFAULT_MAX_DIMENSION = 1600;

    /** @var array<string, int> upload directory => longest-edge pixels */
    public const MAX_DIMENSIONS = [
        'instructors' => 480,
        'gallery' => 1200,
        'hall-of-fame' => 960,
        'products' => 1280,
        'pages' => 1920,
        'course-documents' => 0, // never image-optimised
    ];

    public const WEBP_QUALITY = 82;

    /** @var array<class-string<Model>, list<string>> */
    public const MODEL_IMAGE_ATTRIBUTES = [
        Instructor::class => ['photo'],
        GalleryImage::class => ['image'],
        HallOfFameEntry::class => ['image'],
        Product::class => ['image'],
    ];

    /** @var array<class-string<Settings>, list<string>> */
    public const SETTINGS_IMAGE_PROPERTIES = [
        HomePageSettings::class => ['hero_image', 'about_image_1', 'about_image_2'],
        TandemPageSettings::class => ['intro_image'],
        AffPageSettings::class => ['intro_image'],
        CoachedPageSettings::class => ['image'],
    ];

    public static function maxDimensionFor(string $path): int
    {
        $directory = explode('/', $path)[0];

        return self::MAX_DIMENSIONS[$directory] ?? self::DEFAULT_MAX_DIMENSION;
    }

    /**
     * Whether a stored value is an optimisable upload: a relative public-disk
     * path (bundled site images start with "/") that is not already WebP.
     */
    public static function isOptimisablePath(?string $path): bool
    {
        if ($path === null || $path === '' || str_starts_with($path, '/')) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)
            && self::maxDimensionFor($path) > 0;
    }
}
