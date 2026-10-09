<?php

namespace Tests\Feature\Database;

use App\Contracts\GuardsDeletion;
use App\Models\Concerns\RefusesGuardedDeletion;
use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Structural guard (prompt 008): the database agrees with the delete buttons.
 * Every foreign key whose parent model implements GuardsDeletion refuses the
 * delete (RESTRICT), unless it is pure child data listed below with a reason;
 * and every GuardsDeletion model refuses at the model layer too.
 */
class GuardedParentsRestrictDeletesTest extends TestCase
{
    /**
     * child.column => why it may follow its guarded parent (numbers from
     * audits/reports/fk-delete-rules.md, approved by Ben 9 Oct 2026).
     */
    private const ALLOWED = [
        'course_messages.course_date_id' => '#4 CASCADE: a course can only be deleted with no bookings (#3), so it has no recipients and its messages are pure child data.',
        'course_reminders.course_date_id' => '#5 CASCADE: the course\'s own reminder schedule.',
        'news_articles.course_date_id' => '#6 SET NULL: the article survives and just loses its course link.',
        'product_add_ons.product_id' => '#12 CASCADE: pure child configuration; booked add-on prices are copied onto the booking.',
        'discipline_instructor.discipline_id' => '#27 CASCADE: a pivot.',
    ];

    public function test_every_foreign_key_to_a_guarded_parent_restricts_deletes(): void
    {
        $this->assertSame([], $this->violations($this->foreignKeysToGuardedParents()));
    }

    public function test_the_guard_catches_a_planted_cascade(): void
    {
        $planted = [...$this->foreignKeysToGuardedParents(), 'payments.booking_id' => 'cascade'];

        $this->assertSame(['payments.booking_id → cascade'], $this->violations($planted));
    }

    public function test_the_guard_sees_the_guarded_parents(): void
    {
        $keys = $this->foreignKeysToGuardedParents();

        // Sanity: the walk reaches the FKs the prompt is about (not an empty, vacuous pass).
        $this->assertSame('restrict', $keys['course_dates.product_id'] ?? null);
        $this->assertSame('restrict', $keys['payments.booking_id'] ?? null);
        $this->assertSame('restrict', $keys['course_message_document.document_id'] ?? null);
        $this->assertGreaterThanOrEqual(15, count($keys));
    }

    public function test_every_guarded_model_refuses_at_the_model_layer(): void
    {
        $this->assertSame([], $this->withoutListener($this->guardedModels()), 'GuardsDeletion models without the RefusesGuardedDeletion listener.');
        $this->assertContains(Document::class, $this->guardedModels());
    }

    public function test_the_model_layer_check_catches_a_planted_model(): void
    {
        $planted = new class extends Model implements GuardsDeletion
        {
            public function deletionBlocker(): ?string
            {
                return 'planted';
            }
        };

        $this->assertSame([$planted::class], $this->withoutListener([...$this->guardedModels(), $planted::class]));
    }

    /**
     * @param  list<class-string>  $classes
     * @return list<class-string>
     */
    private function withoutListener(array $classes): array
    {
        return array_values(array_filter(
            $classes,
            fn (string $class): bool => ! in_array(RefusesGuardedDeletion::class, class_uses_recursive($class), true),
        ));
    }

    /**
     * @param  array<string, string>  $keys  child.column => on-delete rule
     * @return list<string>
     */
    private function violations(array $keys): array
    {
        $violations = [];

        foreach ($keys as $key => $rule) {
            if ($rule !== 'restrict' && ! array_key_exists($key, self::ALLOWED)) {
                $violations[] = "{$key} → {$rule}";
            }
        }

        return $violations;
    }

    /** @return array<string, string> child.column => on-delete rule */
    private function foreignKeysToGuardedParents(): array
    {
        $guardedTables = array_map(fn (string $class): string => (new $class)->getTable(), $this->guardedModels());
        $keys = [];

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (in_array($foreignKey['foreign_table'], $guardedTables, true)) {
                    $keys["{$table}.{$foreignKey['columns'][0]}"] = Str::lower((string) $foreignKey['on_delete']);
                }
            }
        }

        ksort($keys);

        return $keys;
    }

    /** @return list<class-string<Model>> */
    private function guardedModels(): array
    {
        $models = [];

        foreach (Finder::create()->files()->in(app_path('Models'))->depth(0)->name('*.php') as $file) {
            $class = 'App\\Models\\'.$file->getBasename('.php');

            if (class_exists($class) && (new ReflectionClass($class))->implementsInterface(GuardsDeletion::class)) {
                $models[] = $class;
            }
        }

        sort($models);

        return $models;
    }
}
