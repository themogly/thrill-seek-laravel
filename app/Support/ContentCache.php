<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Forever-cache for public site content, busted by the models that own it
 * (see FlushesContentCache).
 */
final class ContentCache
{
    private const PREFIX = 'content.';

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::rememberForever(self::PREFIX.$key, $callback);
    }

    public static function flush(string ...$keys): void
    {
        foreach ($keys as $key) {
            Cache::forget(self::PREFIX.$key);
        }
    }
}
