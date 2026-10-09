<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * An Alpine directive outside an `x-data` root is silently dead — nothing in the
 * console. This walks every app view's markup and asserts each directive sits
 * inside a root: an element with `x-data`, or a Livewire component's root element
 * (Livewire registers `[wire:id]` as an Alpine root — livewire.js addRootSelector).
 *
 * A Blade component whose directives have no root of their own is "scope-dependent";
 * then every place that renders it must do so inside a root (checked transitively).
 *
 * Normalisation: `@click` ≡ `x-on:click` and `:attr` ≡ `x-bind:attr` on HTML
 * elements. On `<x-…>` component tags `:prop` is Blade's prop binding, not Alpine,
 * so only `x-*` and `@event` attributes there count (they're forwarded to the
 * rendered element via $attributes).
 *
 * Scope: resources/views/** except resources/views/vendor/ (published third-party
 * mail markup with no Alpine). Mail and PDF views are included — they should hold
 * no Alpine at all, and the walk proves it.
 */
class AlpineDirectivesHaveAScopeTest extends TestCase
{
    private const VOID = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];

    public function test_every_alpine_directive_sits_inside_an_x_data_root(): void
    {
        $files = array_values(array_filter(
            SourceFiles::under('resources/views'),
            fn (string $path): bool => ! str_contains($path, '/resources/views/vendor/'),
        ));
        $this->assertNotEmpty($files);

        $scans = [];
        foreach ($files as $path) {
            $scans[$path] = $this->scan($path);
        }

        // The walker must actually see directives, or it's a guard that always passes.
        $seen = array_sum(array_map(fn (array $scan): int => $scan['directives'], $scans));
        $this->assertGreaterThan(20, $seen, 'The walker found almost no Alpine directives — the parser is broken.');

        // Scope-dependent components, to a fixpoint: unscoped directives of their own,
        // or an unscoped use of another scope-dependent component.
        $dependent = [];
        do {
            $changed = false;
            foreach ($scans as $path => $scan) {
                if (isset($dependent[$path]) || ! str_contains($path, '/resources/views/components/')) {
                    continue;
                }
                if ($scan['unscoped'] !== [] || $this->unscopedUses($scan, $dependent) !== []) {
                    $dependent[$path] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        $violations = [];
        foreach ($scans as $path => $scan) {
            if (str_contains($path, '/resources/views/components/')) {
                continue;
            }
            foreach ($scan['unscoped'] as [$line, $directive]) {
                $violations[] = SourceFiles::relative($path).":{$line} {$directive} has no x-data root";
            }
            foreach ($this->unscopedUses($scan, $dependent) as [$line, $component]) {
                $violations[] = SourceFiles::relative($path).":{$line} renders <x-{$component}> (its Alpine needs a root) outside any x-data";
            }
        }

        $this->assertSame([], $violations, "Alpine directives outside a root never run:\n".implode("\n", $violations));
    }

    /**
     * @param  array{uses: list<array{int, string, bool}>}  $scan
     * @param  array<string, true>  $dependent
     * @return list<array{int, string}>
     */
    private function unscopedUses(array $scan, array $dependent): array
    {
        $unscoped = [];
        foreach ($scan['uses'] as [$line, $component, $scoped]) {
            $file = SourceFiles::componentView($component);
            if (! $scoped && $file !== null && isset($dependent[$file])) {
                $unscoped[] = [$line, $component];
            }
        }

        return $unscoped;
    }

    /**
     * @return array{directives: int, unscoped: list<array{int, string}>, uses: list<array{int, string, bool}>}
     */
    private function scan(string $path): array
    {
        $markup = $this->blank((string) file_get_contents($path));
        $isLivewireView = str_contains($path, '/resources/views/livewire/');

        $stack = [];   // list of [tag name, is a root]
        $directives = 0;
        $unscoped = [];
        $uses = [];
        $sawRootElement = false;

        preg_match_all('/<(\/?)([A-Za-z][\w.:\-]*)((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)>/s', $markup, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($tags as $tag) {
            [$closing, $name, $attributes] = [$tag[1][0] === '/', strtolower($tag[2][0]), $tag[3][0]];
            $line = substr_count($markup, "\n", 0, $tag[0][1]) + 1;

            if ($closing) {
                for ($i = count($stack) - 1; $i >= 0; $i--) {
                    if ($stack[$i][0] === $name) {
                        $stack = array_slice($stack, 0, $i);
                        break;
                    }
                }

                continue;
            }

            $isComponent = str_starts_with($name, 'x-');
            $isRoot = (bool) preg_match('/(^|\s)x-data(\s|=|$)/', $attributes);
            if ($isLivewireView && ! $isComponent && ! $sawRootElement) {
                $isRoot = true; // Livewire's component root element
            }
            if (! $isComponent) {
                $sawRootElement = true;
            }

            $inScope = $isRoot || in_array(true, array_column($stack, 1), true);

            foreach ($this->directivesIn($attributes, $isComponent) as $directive) {
                $directives++;
                if (! $inScope) {
                    $unscoped[] = [$line, $directive];
                }
            }

            if ($isComponent) {
                $uses[] = [$line, substr($name, 2), $inScope];
            }

            $selfClosing = str_ends_with(rtrim($attributes), '/') || in_array($name, self::VOID, true);
            if (! $selfClosing) {
                $stack[] = [$name, $isRoot];
            }
        }

        return ['directives' => $directives, 'unscoped' => $unscoped, 'uses' => $uses];
    }

    /**
     * Alpine attribute names on one tag, normalised to their long form.
     *
     * @return list<string>
     */
    private function directivesIn(string $attributes, bool $isComponent): array
    {
        // `@event` / `:attr` only count with a value (`@endif` inside a tag is Blade);
        // `x-*` may be boolean (`x-cloak`).
        preg_match_all('/(?:^|\s)([@:][A-Za-z][\w\-:.]*(?=\s*=)|x-[A-Za-z][\w\-:.]*(?=\s*=|\s|\/?$))/', $attributes, $matches);

        $found = [];
        foreach ($matches[1] as $attribute) {
            if (str_starts_with($attribute, '@')) {
                $found[] = 'x-on:'.substr($attribute, 1);
            } elseif (str_starts_with($attribute, ':') && ! $isComponent) {
                $found[] = 'x-bind'.$attribute;
            } elseif (preg_match('/^x-[a-z]/', $attribute) && $attribute !== 'x-data') {
                $found[] = $attribute;
            }
        }

        return $found;
    }

    /**
     * Replace everything that isn't markup — comments, PHP, script/style bodies,
     * Blade echoes and directive arguments — with spaces, keeping newlines so line
     * numbers survive. Blade `@if(…)`/`@class(…)` and `{{ $a->b }}` would otherwise
     * read as tag-closing `>` or as Alpine `@event` attributes.
     */
    private function blank(string $source): string
    {
        $keepNewlines = fn (array $m): string => preg_replace('/[^\n]/', ' ', $m[0]) ?? '';

        $patterns = [
            '/\{\{--.*?--\}\}/s',
            '/<!--.*?-->/s',
            '/@php\b.*?@endphp/s',
            '/<\?php.*?\?>/s',
            '/(?<=<script)\b[^>]*>.*?(?=<\/script>)/is',
            '/(?<=<style)\b[^>]*>.*?(?=<\/style>)/is',
            '/\{\{.*?\}\}/s',
            '/\{!!.*?!!\}/s',
            // @directive(…) with balanced parentheses (Blade, not Alpine — Alpine's `@event` is followed by `=`/`.`)
            '/@[a-zA-Z]+\s*(\((?:[^()]++|(?1))*\))/s',
        ];

        foreach ($patterns as $pattern) {
            $source = preg_replace_callback($pattern, $keepNewlines, $source) ?? $source;
        }

        return $source;
    }
}
