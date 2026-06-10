<?php

namespace App\Settings\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Settings store images as either bundled site paths (e.g. "/images/hero.jpg")
 * or paths uploaded to the public disk through the admin panel.
 */
trait ResolvesImagePaths
{
    public function imageUrl(string $path): string
    {
        return str_starts_with($path, '/') ? $path : Storage::disk('public')->url($path);
    }
}
