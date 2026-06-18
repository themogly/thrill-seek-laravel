<?php

namespace App\Support;

/**
 * Builds a WebP `srcset` for full-bleed hero images so phones download a
 * smaller file than desktop. A hero is only responsive when a mobile variant
 * actually exists on disk next to the full image (bundled heroes ship one;
 * uploaded heroes don't until the pipeline generates variants — they fall back
 * to the single pipeline-capped image). The single source of truth shared by
 * the hero `<img>` and its `<link rel="preload">`.
 */
final class ResponsiveImage
{
    /** Longest-edge width of the bundled mobile hero variant. */
    public const MOBILE_WIDTH = 1280;

    /** Natural longest-edge width of a full-size hero. */
    public const FULL_WIDTH = 1920;

    /**
     * The `srcset` value for a hero, or null when no mobile variant exists
     * (so the caller emits a plain `src` only).
     */
    public static function heroSrcset(string $url): ?string
    {
        $mobile = self::mobileVariantUrl($url);

        if ($mobile === null) {
            return null;
        }

        return $mobile.' '.self::MOBILE_WIDTH.'w, '.$url.' '.self::FULL_WIDTH.'w';
    }

    /**
     * The URL of the `-1280` sibling variant when it exists on the public
     * disk; null otherwise. Only bundled `/images/*.webp` heroes carry one.
     */
    public static function mobileVariantUrl(string $url): ?string
    {
        if (! str_starts_with($url, '/images/') || ! str_ends_with($url, '.webp')) {
            return null;
        }

        $variant = substr($url, 0, -strlen('.webp')).'-'.self::MOBILE_WIDTH.'.webp';

        return is_file(public_path(ltrim($variant, '/'))) ? $variant : null;
    }
}
