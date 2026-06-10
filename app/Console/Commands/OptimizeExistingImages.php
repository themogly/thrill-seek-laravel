<?php

namespace App\Console\Commands;

use App\Jobs\OptimizeUploadedImage;
use App\Support\ImageOptimization;
use Illuminate\Console\Command;

class OptimizeExistingImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Queue optimisation for every already-uploaded image referenced by content models or settings';

    public function handle(): int
    {
        $paths = collect();

        foreach (ImageOptimization::MODEL_IMAGE_ATTRIBUTES as $modelClass => $attributes) {
            foreach ($attributes as $attribute) {
                $paths = $paths->merge($modelClass::query()->pluck($attribute));
            }
        }

        foreach (ImageOptimization::SETTINGS_IMAGE_PROPERTIES as $settingsClass => $properties) {
            $settings = app($settingsClass);

            foreach ($properties as $property) {
                $paths->push($settings->{$property});
            }
        }

        $queued = $paths
            ->filter(fn (?string $path): bool => ImageOptimization::isOptimisablePath($path))
            ->unique()
            ->each(fn (string $path) => OptimizeUploadedImage::dispatch($path))
            ->count();

        $this->info("Queued optimisation for {$queued} image(s).");

        return self::SUCCESS;
    }
}
