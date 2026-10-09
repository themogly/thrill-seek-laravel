<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Prompt 025 guard: an admin select that lists only what's still bookable
 * (`upcoming()` / `upcomingOpen()`) builds its options through
 * AdminOptions::bookablePlusCurrent(), so a record whose slot or course has
 * since passed still shows its label and still saves. Filament refuses any value
 * that isn't among the options, so without it the record can't be saved at all.
 */
class SelectsKeepTheirCurrentValueTest extends TestCase
{
    /** file => why its time-filtered select has no stored value to keep */
    private const NO_STORED_VALUE = [
        'app/Filament/Resources/Bookings/Tables/BookingsTable.php' => 'The Reschedule action picks a NEW slot; its form starts empty and stores nothing itself.',
    ];

    public function test_time_filtered_selects_keep_the_current_value(): void
    {
        $sources = [];
        foreach (SourceFiles::under('app/Filament', '.php') as $file) {
            $sources[substr($file, strlen(SourceFiles::root()) + 1)] = (string) file_get_contents($file);
        }

        $this->assertSame([], $this->violations($sources));
    }

    public function test_the_guard_catches_a_planted_bare_upcoming_select(): void
    {
        $planted = ['app/Filament/Planted.php' => "Select::make('tandem_date_id')->options(fn (): array => TandemDate::upcoming()->get()->pluck('id', 'id')->all())"];

        $this->assertSame(['app/Filament/Planted.php:1'], $this->violations($planted));
    }

    /**
     * @param  array<string, string>  $sources
     * @return list<string>
     */
    private function violations(array $sources): array
    {
        $violations = [];

        foreach ($sources as $file => $code) {
            if (array_key_exists($file, self::NO_STORED_VALUE)) {
                continue;
            }

            preg_match_all('/::(?:upcoming|upcomingOpen)\(\)/', $code, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [, $offset]) {
                $before = substr($code, max(0, $offset - 200), min(200, $offset));

                if (! str_contains($before, 'AdminOptions::bookablePlusCurrent(')) {
                    $violations[] = $file.':'.(substr_count(substr($code, 0, $offset), "\n") + 1);
                }
            }
        }

        return $violations;
    }
}
