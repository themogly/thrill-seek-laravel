<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;

/**
 * Locks an image `FileUpload` to a fixed aspect ratio using Filament v5's built-in
 * image editor (Cropper.js) — the ONE way we enforce image shape in the admin, so
 * the front-end display ratio is guaranteed at the source.
 *
 * The owner uploads any image; the cropper auto-opens locked to the given ratio
 * (and centre-crops to it if they don't adjust), so the output is always the right
 * shape with the subject kept in frame. There is NO hard rejection — the crop tool
 * is the UX, so a wrong-shaped upload is never a dead end. The cropped file still
 * flows through the `OptimizeUploadedImage` → WebP pipeline unchanged.
 *
 * Use ONLY where the display needs a fixed shape; leave free-form fields untouched.
 * Per-field ratios live in DECISIONS.md. Ratios are CSS-style strings, e.g. '1:1',
 * '16:9', '3:4', '16:10'.
 */
final class ImageCrop
{
    public static function ratio(FileUpload $field, string $ratio): FileUpload
    {
        return $field
            // Not Filament's image() helper: it resets the accepted types to `image/*`,
            // which lets SVG back in. The field comes from AdminImages::upload().
            ->acceptedFileTypes(AdminImages::TYPES)
            ->imageEditor()
            // A single ratio option (no free/null) → the crop box is LOCKED to it.
            ->imageEditorAspectRatios([$ratio])
            ->imageAspectRatio($ratio)
            // Centre-crop to the ratio even if the editor isn't opened…
            ->automaticallyCropImagesToAspectRatio()
            // …and auto-open the cropper when the source doesn't match, so the
            // owner positions the subject rather than accepting a centre crop.
            ->automaticallyOpenImageEditorForAspectRatio();
    }
}
