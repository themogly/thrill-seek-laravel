<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\ContentCache;

/**
 * Busts the public-site content cache whenever a content model changes.
 * Models declare which cache keys they own via contentCacheKeys().
 */
trait FlushesContentCache
{
    protected static function bootFlushesContentCache(): void
    {
        $flush = fn () => ContentCache::flush(...static::contentCacheKeys());

        static::saved($flush);
        static::deleted($flush);
    }

    /** @return list<string> */
    abstract protected static function contentCacheKeys(): array;
}
