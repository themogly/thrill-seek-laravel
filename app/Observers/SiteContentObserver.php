<?php

namespace App\Observers;

use App\Support\SiteContent;
use Illuminate\Database\Eloquent\Model;

/**
 * Busts the public-site content cache whenever a content model changes.
 * Registered on every model listed in SiteContent::KEYS_BY_MODEL.
 */
class SiteContentObserver
{
    public function saved(Model $model): void
    {
        SiteContent::flushFor($model::class);
    }

    public function deleted(Model $model): void
    {
        SiteContent::flushFor($model::class);
    }
}
