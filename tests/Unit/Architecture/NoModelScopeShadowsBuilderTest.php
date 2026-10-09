<?php

namespace Tests\Unit\Architecture;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * A local scope named like a query-builder method hijacks that method for the
 * model: Eloquent routes `forPage(1, 10)` to `scopeForPage()` before the builder
 * ever sees it. `Faq::scopeForPage` did exactly that and 500'd the admin FAQ list
 * (Filament paginates with forPage). Every model's scopes must stay clear of
 * every public Eloquent / Query builder method.
 */
class NoModelScopeShadowsBuilderTest extends TestCase
{
    public function test_no_model_scope_shares_a_name_with_a_builder_method(): void
    {
        $builderMethods = array_map('strtolower', [
            ...array_map(fn (ReflectionMethod $m): string => $m->getName(), (new ReflectionClass(EloquentBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC)),
            ...array_map(fn (ReflectionMethod $m): string => $m->getName(), (new ReflectionClass(QueryBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC)),
        ]);

        $models = 0;
        $collisions = [];
        foreach (SourceFiles::under('app/Models', '.php') as $path) {
            $class = 'App\\Models\\'.basename($path, '.php');
            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }
            $models++;

            foreach ((new ReflectionClass($class))->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $class || ! preg_match('/^scope([A-Z]\w*)$/', $method->getName(), $m)) {
                    continue;
                }
                if (in_array(strtolower($m[1]), $builderMethods, true)) {
                    $collisions[] = "{$class}::{$method->getName()}() shadows Builder::".lcfirst($m[1]).'()';
                }
            }
        }

        $this->assertGreaterThan(10, $models, 'Found almost no models — the scan is broken.');
        $this->assertContains('forpage', $builderMethods, 'Builder method list looks wrong — the scan is broken.');
        $this->assertSame([], $collisions, "Rename these scopes — they hijack the builder method of the same name:\n".implode("\n", $collisions));
    }
}
