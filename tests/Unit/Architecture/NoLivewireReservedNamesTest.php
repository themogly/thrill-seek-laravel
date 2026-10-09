<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * A public component method named like a `$wire` alias (`commit`, `get`, `set`,
 * `call`, `dispatch`, `watch`, …) is unreachable from the front end:
 * `$wire.commit` resolves to Livewire's own before it reaches the component.
 *
 * The alias list is parsed from the vendored Livewire dist, not hand-kept, so a
 * Livewire bump that adds an alias is picked up automatically.
 */
class NoLivewireReservedNamesTest extends TestCase
{
    public function test_no_livewire_component_method_shadows_a_wire_alias(): void
    {
        $aliases = $this->wireAliases();

        $violations = [];
        foreach ($this->componentClasses() as $class) {
            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Only methods the app declares (its own or from its own traits) —
                // Livewire's base-class API is Livewire's business.
                if (! str_starts_with($method->getDeclaringClass()->getName(), 'App\\')) {
                    continue;
                }

                if (in_array($method->getName(), $aliases, true)) {
                    $violations[] = "{$class}::{$method->getName()}() shadows \$wire.{$method->getName()}";
                }
            }
        }

        $this->assertSame([], $violations, "Rename these — the front end can never call them:\n".implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private function wireAliases(): array
    {
        $dist = SourceFiles::root().'/vendor/livewire/livewire/dist/livewire.js';
        $this->assertFileExists($dist, 'Vendored Livewire dist not found — cannot read the $wire alias map.');

        $source = (string) file_get_contents($dist);
        $this->assertSame(1, preg_match('/var aliases = \{(.*?)\};/s', $source, $block), 'Could not find the `var aliases = {…}` map in livewire.js — the dist changed shape; update this parser.');

        preg_match_all('/"(\w+)":\s*"\$\w+"/', $block[1], $matches);
        $aliases = $matches[1];

        // A parser that silently finds nothing is a guard that always passes.
        $this->assertGreaterThanOrEqual(10, count($aliases), 'Parsed fewer than 10 $wire aliases from livewire.js — the parser is broken.');
        $this->assertContains('commit', $aliases);

        return $aliases;
    }

    /**
     * @return list<class-string>
     */
    private function componentClasses(): array
    {
        $classes = [];
        foreach (SourceFiles::under('app/Livewire', '.php') as $path) {
            $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen(SourceFiles::root().'/app/')));
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }
        $this->assertNotEmpty($classes, 'Found no Livewire component classes — the scan itself is broken.');

        return $classes;
    }
}
