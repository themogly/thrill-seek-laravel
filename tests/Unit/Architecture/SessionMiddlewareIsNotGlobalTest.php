<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * CLAUDE.md architecture rule 9: middleware that reads the session registers on
 * the `web` group (after StartSession), never on the global stack. Global
 * middleware runs before the session loads, reads null and degrades open, while
 * in-process tests that pre-seed the store stay green (false-green.md #6).
 *
 * Grep proves registration, not order — the request-level half lives in
 * Tests\Feature\Security\SessionSurvivesARealRequestTest.
 */
class SessionMiddlewareIsNotGlobalTest extends TestCase
{
    public function test_session_reading_middleware_registers_on_web_never_globally(): void
    {
        $middlewareFiles = SourceFiles::under('app/Http/Middleware', '.php');
        $this->assertNotEmpty($middlewareFiles, 'Found no app middleware — the scan itself is broken.');

        $registration = (string) file_get_contents(SourceFiles::root().'/bootstrap/app.php');
        $global = $this->classesIn($registration, '/\$middleware->(?:append|prepend|use)\(\s*\[?(.*?)\]?\s*\)\s*;/s');
        $web = [
            ...$this->classesIn($registration, '/\$middleware->web\((.*?)\)\s*;/s'),
            ...$this->classesIn($registration, "/\\\$middleware->(?:append|prepend)ToGroup\\(\\s*'web'\\s*,(.*?)\\)\\s*;/s"),
        ];
        // Route aliases only ever run on routes, and every app route is in routes/web.php
        // (inside the web group) — so an aliased reader is after StartSession too.
        $aliased = $this->classesIn($registration, '/\$middleware->alias\((.*?)\)\s*;/s');

        $violations = [];
        foreach ($middlewareFiles as $path) {
            $source = (string) file_get_contents($path);
            if (! preg_match('/\bsession\(|->session\(\)|\bSession::/', $source)) {
                continue;
            }

            $class = basename($path, '.php');
            if (in_array($class, $global, true)) {
                $violations[] = "{$class} reads the session but is registered on the GLOBAL stack";
            } elseif (! in_array($class, $web, true) && ! in_array($class, $aliased, true)) {
                $violations[] = "{$class} reads the session but is not registered on the web group";
            }
        }

        $this->assertSame([], $violations, "Register session readers with \$middleware->web(append: […]) in bootstrap/app.php:\n".implode("\n", $violations));
    }

    /**
     * Short class names referenced as `Foo::class` inside each match of $pattern.
     *
     * @return list<string>
     */
    private function classesIn(string $source, string $pattern): array
    {
        preg_match_all($pattern, $source, $calls);

        $classes = [];
        foreach ($calls[1] as $arguments) {
            preg_match_all('/([A-Za-z_\\\\]+)::class/', $arguments, $matches);
            foreach ($matches[1] as $name) {
                $classes[] = substr((string) strrchr('\\'.$name, '\\'), 1);
            }
        }

        return $classes;
    }
}
