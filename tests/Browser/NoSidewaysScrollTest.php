<?php

namespace Tests\Browser;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

/**
 * Prompt 028: no public page scrolls sideways on a phone.
 *
 * The rest of the suite renders HTML but never lays it out, and axe doesn't check
 * overflow, so `/aff` shipped 451px wide at 375 (a nowrap button in a card in an
 * auto-sized grid track) with every check green — false-green.md, "the instrument
 * looked at the wrong thing". This test lays the real pages out in Chromium.
 *
 * It migrates and seeds a throwaway SQLite file (the seeders reproduce the live
 * content), serves the app from it with PHP's built-in server, and loads every
 * URL in `/sitemap.xml` plus the booking/voucher/login pages at 375×667. A page
 * fails when `scrollWidth > clientWidth`; the message names the widest element
 * past the right edge that isn't inside an `overflow-x` container.
 *
 * Needs node + Playwright's Chromium (`npx playwright install chromium`) and built
 * assets (`npm run build`, or `npm run dev` running) — it refuses stale ones rather
 * than measure an old stylesheet. Running in `composer check` (not a separate
 * opt-in suite) and refusing stale assets: OVERNIGHT-DEFAULT — CONFIRM (DECISIONS).
 */
class NoSidewaysScrollTest extends TestCase
{
    private const VIEWPORT = '375x667';

    /** Pages a visitor reaches that the sitemap (indexable pages only) doesn't list. */
    private const EXTRA_PATHS = ['/book/tandem', '/book/aff', '/vouchers', '/account/login'];

    /** @var list<Process> */
    private array $servers = [];

    private ?string $database = null;

    private ?string $plantedDirectory = null;

    protected function tearDown(): void
    {
        foreach ($this->servers as $server) {
            $server->stop(1);
        }

        if ($this->database !== null) {
            @unlink($this->database);
        }

        if ($this->plantedDirectory !== null) {
            array_map('unlink', glob($this->plantedDirectory.'/*') ?: []);
            @rmdir($this->plantedDirectory);
        }

        parent::tearDown();
    }

    public function test_no_page_scrolls_sideways_at_phone_width(): void
    {
        $this->assertAssetsAreCurrent();

        $base = $this->serveTheApp();
        $paths = array_values(array_unique([...$this->sitemapPaths($base), ...self::EXTRA_PATHS]));
        $this->assertGreaterThan(10, count($paths), 'The sitemap listed too few pages — the walk itself is broken.');

        $failures = [];
        foreach ($this->measure($base, $paths) as $path => $page) {
            if ($page['status'] !== 200) {
                $failures[] = "{$path}: HTTP {$page['status']}";
            } elseif ($page['failedRequests'] !== []) {
                $failures[] = "{$path}: assets failed to load, so the layout wasn't the real one: ".implode(', ', $page['failedRequests']);
            } elseif ($page['scrollWidth'] > $page['clientWidth']) {
                $failures[] = "{$path}: {$page['scrollWidth']}px wide in a {$page['clientWidth']}px screen — {$page['culprit']}";
            }
        }

        $this->assertSame([], $failures, 'Pages that scroll sideways at '.self::VIEWPORT.":\n".implode("\n", $failures));
    }

    public function test_the_walk_catches_a_planted_wide_element_and_allows_a_scroll_region(): void
    {
        $this->plantedDirectory = sys_get_temp_dir().'/no-sideways-scroll-'.bin2hex(random_bytes(4));
        mkdir($this->plantedDirectory);
        $page = '<!doctype html><meta name="viewport" content="width=device-width, initial-scale=1"><body style="margin:0">%s</body>';
        file_put_contents($this->plantedDirectory.'/wide.html', sprintf($page, '<p>fits</p><div id="planted" style="width:600px">too wide</div>'));
        file_put_contents($this->plantedDirectory.'/scroller.html', sprintf($page, '<div style="overflow-x:auto"><table style="width:900px"><tr><td>a wide table in its own scroll region</td></tr></table></div>'));

        $base = $this->serve(['-t', $this->plantedDirectory], []);
        $results = $this->measure($base, ['/wide.html', '/scroller.html']);

        $this->assertSame(600, $results['/wide.html']['scrollWidth']);
        $this->assertStringContainsString('#planted', (string) $results['/wide.html']['culprit']);
        $this->assertSame(375, $results['/scroller.html']['scrollWidth'], 'A wide table inside an overflow-x region must not widen the page.');
        $this->assertNull($results['/scroller.html']['culprit']);
    }

    private function assertAssetsAreCurrent(): void
    {
        $root = $this->root();

        $this->assertFileDoesNotExist($root.'/bootstrap/cache/config.php', 'Config is cached: the served app would ignore the throwaway database and read the real one. Run `php artisan config:clear`.');

        if (is_file($root.'/public/hot')) {
            return; // `npm run dev` serves the live sources.
        }

        $manifest = $root.'/public/build/manifest.json';
        $this->assertFileExists($manifest, 'No built assets: run `npm run build` (or `npm run dev`).');

        $newest = 0;
        $newestFile = '';
        foreach (['resources/css', 'resources/js', 'resources/views'] as $directory) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if ($file->getMTime() > $newest) {
                    $newest = $file->getMTime();
                    $newestFile = $file->getPathname();
                }
            }
        }

        $this->assertGreaterThanOrEqual($newest, (int) filemtime($manifest), "The built assets are older than {$newestFile}: run `npm run build` so the measurement is of this tree, not an old one.");
    }

    private function serveTheApp(): string
    {
        $this->database = sys_get_temp_dir().'/no-sideways-scroll-'.bin2hex(random_bytes(4)).'.sqlite';
        touch($this->database);

        $env = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->database,
            'DB_URL' => '',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'BROADCAST_CONNECTION' => 'null',
            'PULSE_ENABLED' => 'false',
            'TELESCOPE_ENABLED' => 'false',
            'NIGHTWATCH_ENABLED' => 'false',
        ];

        $migrate = new Process([PHP_BINARY, 'artisan', 'migrate', '--seed', '--force', '--no-interaction'], $this->root(), $env, null, 180);
        $migrate->run();
        $this->assertTrue($migrate->isSuccessful(), "Seeding the throwaway database failed:\n".$migrate->getOutput().$migrate->getErrorOutput());

        return $this->serve(
            ['-t', $this->root().'/public', $this->root().'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            $env,
        );
    }

    /**
     * @param  list<string>  $arguments
     * @param  array<string, string>  $env
     */
    private function serve(array $arguments, array $env): string
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($socket);
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $base = "http://127.0.0.1:{$port}";
        $server = new Process(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", ...$arguments],
            $this->root().'/public',
            [...$env, 'APP_URL' => $base],
            null,
            null,
        );
        // One process, no captured output: forked PHP_CLI_SERVER_WORKERS outlive
        // stop() and hold the pipes open, which hangs the suite at teardown.
        $server->disableOutput();
        $server->start();
        $this->servers[] = $server;

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($connection !== false) {
                fclose($connection);

                return $base;
            }
            usleep(50_000);
        }

        $this->fail('The test server never started on port '.$port.'.');
    }

    /**
     * @return list<string>
     */
    private function sitemapPaths(string $base): array
    {
        $xml = @file_get_contents($base.'/sitemap.xml');
        $this->assertIsString($xml, 'The served app returned no sitemap.');
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $matches);

        return array_values(array_unique(array_map(
            fn (string $url): string => (string) (parse_url(html_entity_decode($url), PHP_URL_PATH) ?? '/'),
            $matches[1],
        )));
    }

    /**
     * @param  list<string>  $paths
     * @return array<string, array{status: int, scrollWidth: int, clientWidth: int, culprit: ?string, failedRequests: list<string>}>
     */
    private function measure(string $base, array $paths): array
    {
        $walk = new Process(['node', 'tests/Browser/no-sideways-scroll.mjs', $base, self::VIEWPORT, ...$paths], $this->root(), null, null, 300);
        $walk->run();
        $this->assertTrue($walk->isSuccessful(), "The browser walk failed (is Playwright's Chromium installed? `npx playwright install chromium`):\n".$walk->getErrorOutput());

        $results = json_decode($walk->getOutput(), true);
        $this->assertIsArray($results, 'The browser walk printed no results.');
        $this->assertSame($paths, array_keys($results), 'The walk skipped a page.');

        /** @var array<string, array{status: int, scrollWidth: int, clientWidth: int, culprit: ?string, failedRequests: list<string>}> $results */
        return $results;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
