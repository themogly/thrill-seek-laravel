<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\ImageOptimization;
use App\Support\SiteContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Resizes an uploaded image to its context's maximum dimension, re-encodes
 * it as WebP (which also strips metadata) and rewrites every model
 * attribute / settings property that referenced the original path. The
 * original file is kept as a fallback.
 */
class OptimizeUploadedImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path) {}

    public function handle(): void
    {
        $disk = Storage::disk('public');

        if (! ImageOptimization::isOptimisablePath($this->path) || ! $disk->exists($this->path)) {
            return;
        }

        $webpPath = preg_replace('/\.\w+$/', '.webp', $this->path);

        if ($webpPath === null || $webpPath === $this->path) {
            return;
        }

        try {
            $image = (new ImageManager(new Driver))->decodeBinary((string) $disk->get($this->path));

            $max = ImageOptimization::maxDimensionFor($this->path);
            $image = $image->scaleDown(width: $max, height: $max);

            // Re-encoding via GD also strips EXIF/metadata.
            $encoded = $image->encode(new WebpEncoder(quality: ImageOptimization::WEBP_QUALITY));
            $disk->put($webpPath, (string) $encoded);
        } catch (\Throwable $e) {
            Log::error('Image optimisation failed', ['path' => $this->path, 'exception' => $e->getMessage()]);

            return;
        }

        $this->rewriteReferences($webpPath);
    }

    /**
     * Point every known reference at the optimised file. Model updates are
     * quiet so observers don't re-trigger optimisation; the site-content
     * cache is busted explicitly instead.
     */
    private function rewriteReferences(string $webpPath): void
    {
        foreach (ImageOptimization::MODEL_IMAGE_ATTRIBUTES as $modelClass => $attributes) {
            foreach ($attributes as $attribute) {
                $modelClass::query()
                    ->where($attribute, $this->path)
                    ->get()
                    ->each(function ($model) use ($attribute, $webpPath): void {
                        $model->forceFill([$attribute => $webpPath])->saveQuietly();
                    });
            }

            SiteContent::flushFor($modelClass);
        }

        foreach (ImageOptimization::SETTINGS_IMAGE_PROPERTIES as $settingsClass => $properties) {
            $settings = app($settingsClass);
            $updates = [];

            foreach ($properties as $property) {
                if (($settings->toArray()[$property] ?? null) === $this->path) {
                    $updates[$property] = $webpPath;
                }
            }

            if ($updates !== []) {
                $settings->fill($updates)->save();
            }
        }
    }
}
