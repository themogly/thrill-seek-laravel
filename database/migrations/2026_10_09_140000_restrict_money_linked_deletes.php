<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The database refuses deletes that money, a booking or a customer's history
 * hangs off (prompt 008 Phase 2; table approved by Ben 9 Oct 2026, see
 * audits/reports/fk-delete-rules.md and DECISIONS). Only the delete rule of each
 * foreign key changes: no column, row or index is touched.
 *
 * Before changing anything it checks every affected column for orphaned values
 * (a child pointing at a parent that no longer exists, which the re-added
 * constraint would reject) and aborts with the list instead of fixing data.
 */
return new class extends Migration
{
    /**
     * child table => [column, parent table, rule before this migration], in the
     * proposal's numbering. Every one becomes RESTRICT.
     *
     * @var array<string, list<array{0: string, 1: string, 2: string}>>
     */
    private const CHANGES = [
        'payments' => [
            ['booking_id', 'bookings', 'set null'],   // #1
            ['enquiry_id', 'enquiries', 'set null'],  // #16
        ],
        'vouchers' => [
            ['booking_id', 'bookings', 'set null'],   // #2
            ['product_id', 'products', 'set null'],   // #11
            ['payment_id', 'payments', 'set null'],   // #15
        ],
        'bookings' => [
            ['course_date_id', 'course_dates', 'set null'], // #3
            ['tandem_date_id', 'tandem_dates', 'set null'], // #7
            ['product_id', 'products', 'set null'],         // #9
            ['enquiry_id', 'enquiries', 'set null'],        // #17
            ['customer_id', 'customers', 'set null'],       // #19
        ],
        'course_dates' => [
            ['product_id', 'products', 'cascade'],    // #8
        ],
        'enquiries' => [
            ['product_id', 'products', 'set null'],   // #10
            ['customer_id', 'customers', 'set null'], // #20
        ],
        'course_message_document' => [
            ['document_id', 'documents', 'cascade'],  // #26
        ],
    ];

    public function up(): void
    {
        $this->abortOnOrphans();
        $this->apply(fn (array $change): string => 'restrict');
    }

    public function down(): void
    {
        $this->apply(fn (array $change): string => $change[2]);
    }

    /** @param  callable(array{0: string, 1: string, 2: string}): string  $rule */
    private function apply(callable $rule): void
    {
        foreach (self::CHANGES as $table => $changes) {
            $names = $this->foreignKeyNames($table);

            Schema::table($table, function (Blueprint $blueprint) use ($table, $changes, $names, $rule): void {
                foreach ($changes as $change) {
                    [$column, $parent] = $change;

                    // SQLite drops by column (it rebuilds the table); MySQL needs the real
                    // constraint name, which isn't always the conventional one
                    // (bookings.tandem_date_id is still named after its old column).
                    $blueprint->dropForeign(DB::getDriverName() === 'sqlite' ? [$column] : $names[$column]);
                    $blueprint->foreign($column, "{$table}_{$column}_foreign")
                        ->references('id')->on($parent)->onDelete($rule($change));
                }
            });
        }
    }

    /** @return array<string, string> column => constraint name */
    private function foreignKeyNames(string $table): array
    {
        $names = [];

        foreach (Schema::getForeignKeys($table) as $key) {
            $names[$key['columns'][0]] = (string) $key['name'];
        }

        return $names;
    }

    private function abortOnOrphans(): void
    {
        $orphans = [];

        foreach (self::CHANGES as $table => $changes) {
            foreach ($changes as [$column, $parent]) {
                $count = DB::table($table)
                    ->whereNotNull("{$table}.{$column}")
                    ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from($parent)->whereColumn("{$parent}.id", "{$table}.{$column}"))
                    ->count();

                if ($count > 0) {
                    $orphans[] = "{$table}.{$column} → {$parent}: {$count} row(s)";
                }
            }
        }

        if ($orphans !== []) {
            throw new RuntimeException(
                "Not changing any delete rule: these rows point at a parent that no longer exists. Fix the data first, then migrate again.\n"
                .implode("\n", $orphans)
            );
        }
    }
};
