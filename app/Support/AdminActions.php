<?php

namespace App\Support;

use App\Contracts\GuardsDeletion;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The one way to put a Delete button on a record that other data depends on.
 * The button is disabled — and Filament refuses a disabled action even when it
 * is called directly — whenever the record reports a deletion blocker, and the
 * tooltip tells the owner why and what to do instead.
 */
class AdminActions
{
    public static function guardedDelete(): DeleteAction
    {
        return DeleteAction::make()
            ->disabled(fn (GuardsDeletion $record): bool => $record->deletionBlocker() !== null)
            ->tooltip(fn (GuardsDeletion $record): ?string => $record->deletionBlocker());
    }

    /**
     * Bulk delete that only deletes the selected records nothing depends on,
     * and says how many it skipped.
     */
    public static function guardedBulkDelete(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->action(function (Collection $records): void {
                [$safe, $blocked] = $records->partition(
                    fn (Model $record): bool => ! $record instanceof GuardsDeletion || $record->deletionBlocker() === null,
                );

                $safe->each->delete();

                if ($blocked->isNotEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title("Deleted {$safe->count()}, kept {$blocked->count()}")
                        ->body('Some were kept because bookings or other records depend on them. Open one to see why.')
                        ->send();
                }
            });
    }
}
