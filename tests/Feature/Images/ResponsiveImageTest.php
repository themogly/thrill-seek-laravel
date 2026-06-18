<?php

namespace Tests\Feature\Images;

use App\Support\ResponsiveImage;
use Tests\TestCase;

class ResponsiveImageTest extends TestCase
{
    public function test_bundled_hero_gets_a_mobile_variant_srcset(): void
    {
        // The bundled hero ships a -1280 sibling on the public disk.
        $this->assertSame(
            '/images/hero-skydive-1280.webp',
            ResponsiveImage::mobileVariantUrl('/images/hero-skydive.webp'),
        );

        $this->assertSame(
            '/images/hero-skydive-1280.webp 1280w, /images/hero-skydive.webp 1920w',
            ResponsiveImage::heroSrcset('/images/hero-skydive.webp'),
        );
    }

    public function test_images_without_a_variant_fall_back_to_a_single_source(): void
    {
        // tandem.webp has no -1280 sibling; uploads resolve to a /storage URL;
        // non-webp is never responsive. All degrade to a plain src (null srcset).
        $this->assertNull(ResponsiveImage::heroSrcset('/images/tandem.webp'));
        $this->assertNull(ResponsiveImage::heroSrcset('/storage/pages/custom-hero.webp'));
        $this->assertNull(ResponsiveImage::heroSrcset('/images/hero-skydive.jpg'));
    }
}
