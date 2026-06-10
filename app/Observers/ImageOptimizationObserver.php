<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\OptimizeUploadedImage;
use App\Support\ImageOptimization;
use Illuminate\Database\Eloquent\Model;

/**
 * Queues optimisation for any freshly-uploaded image on a content model.
 * Registered on every model listed in ImageOptimization::MODEL_IMAGE_ATTRIBUTES.
 */
class ImageOptimizationObserver
{
    public function saved(Model $model): void
    {
        foreach (ImageOptimization::MODEL_IMAGE_ATTRIBUTES[$model::class] ?? [] as $attribute) {
            $path = $model->getAttribute($attribute);

            if ($model->wasChanged($attribute) && ImageOptimization::isOptimisablePath($path)) {
                OptimizeUploadedImage::dispatch($path);
            }
        }
    }
}
