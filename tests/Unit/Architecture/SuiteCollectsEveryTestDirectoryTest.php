<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * A tests/ directory that no <testsuite> lists never runs, and the suite is
 * greener than the code. Every top-level tests/ directory holding a *Test.php
 * must be collected by BOTH phpunit configs (SQLite default + MySQL parity).
 */
class SuiteCollectsEveryTestDirectoryTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function configs(): array
    {
        return [
            'phpunit.xml' => ['phpunit.xml'],
            'phpunit.mysql.xml' => ['phpunit.mysql.xml'],
        ];
    }

    #[DataProvider('configs')]
    public function test_every_test_directory_is_in_a_testsuite(string $config): void
    {
        $root = dirname(__DIR__, 3);
        $xml = simplexml_load_file($root.'/'.$config);
        $this->assertNotFalse($xml, "{$config} must parse.");

        $collected = [];
        foreach ($xml->xpath('//testsuites/testsuite/directory') ?: [] as $directory) {
            $collected[] = rtrim(trim((string) $directory), '/');
        }

        $testDirectories = $this->topLevelTestDirectories($root.'/tests');
        $this->assertNotEmpty($testDirectories, 'Found no test directories — the scan itself is broken.');

        foreach ($testDirectories as $directory) {
            $this->assertContains(
                'tests/'.$directory,
                $collected,
                "tests/{$directory} holds *Test.php files but no <testsuite> in {$config} collects it — those tests never run.",
            );
        }
    }

    /**
     * @return list<string>
     */
    private function topLevelTestDirectories(string $testsPath): array
    {
        $directories = [];

        foreach (glob($testsPath.'/*', GLOB_ONLYDIR) ?: [] as $path) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), 'Test.php')) {
                    $directories[] = basename($path);
                    break;
                }
            }
        }

        return $directories;
    }
}
