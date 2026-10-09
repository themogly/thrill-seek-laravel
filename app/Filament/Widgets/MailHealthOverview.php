<?php

namespace App\Filament\Widgets;

use App\Support\MailHealth;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Would anyone notice email had stopped? Failed sends and a broken mail setup
 * surface here, on the screen the owner opens every day.
 */
class MailHealthOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $health = app(MailHealth::class);
        $failed = $health->failedLastWeek();
        $warnings = $health->warnings();
        $failedTotal = array_sum($failed);

        return [
            Stat::make('Failed emails (last 7 days)', (string) $failedTotal)
                ->description($failedTotal === 0
                    ? 'Every email went out'
                    : collect($failed)->map(fn (int $count, string $type): string => "{$type}: {$count}")->implode(', '))
                ->color($failedTotal === 0 ? 'success' : 'danger'),
            Stat::make('Email setup', $warnings === [] ? 'OK' : count($warnings).' problem(s)')
                ->description($warnings === [] ? 'Ready to send' : implode(' ', $warnings))
                ->color($warnings === [] ? 'success' : 'danger'),
        ];
    }
}
