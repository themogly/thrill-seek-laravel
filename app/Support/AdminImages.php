<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;

/**
 * The ONE way to build an admin image upload (like AdminDates for dates).
 * Photos only: JPEG, PNG or WebP. No SVG, which can carry script and these files
 * are served from the public disk, and no GIF/HEIC, which the WebP optimiser
 * doesn't expect. 12 MB matches Livewire's own temporary-upload cap, so the owner
 * gets Filament's plain message instead of a generic failure.
 * OVERNIGHT-DEFAULT — CONFIRM (types and size; see DECISIONS, prompt 025).
 * Callers chain disk/directory/labels, and ImageCrop::ratio() where a shape matters.
 * Guard: ImageUploadsUseTheFactoryTest.
 */
final class AdminImages
{
    /** @var list<string> */
    public const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const MAX_KB = 12 * 1024;

    public static function upload(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->acceptedFileTypes(self::TYPES)
            ->maxSize(self::MAX_KB);
    }
}
