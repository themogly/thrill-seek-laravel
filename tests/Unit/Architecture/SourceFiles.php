<?php

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Filesystem helpers shared by the structural guards. Plain PHP, no app boot.
 */
final class SourceFiles
{
    public static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Every file under a project-relative directory whose name ends with $suffix.
     *
     * @return list<string> absolute paths, sorted
     */
    public static function under(string $directory, string $suffix = '.blade.php'): array
    {
        $path = self::root().'/'.$directory;
        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /**
     * Resolve `<x-ui.date-field>` to its anonymous component view, or null when it
     * isn't an app component (vendor `x-filament::…`, `x-mail::…`, class components).
     */
    public static function componentView(string $name): ?string
    {
        if (str_contains($name, '::')) {
            return null;
        }

        $base = self::root().'/resources/views/components/'.str_replace('.', '/', $name);

        foreach ([$base.'.blade.php', $base.'/index.blade.php'] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function relative(string $path): string
    {
        return substr($path, strlen(self::root()) + 1);
    }
}
