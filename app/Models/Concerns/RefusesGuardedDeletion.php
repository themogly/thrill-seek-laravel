<?php

namespace App\Models\Concerns;

use App\Contracts\GuardsDeletion;
use App\Exceptions\DeletionBlockedException;

/**
 * The model layer of the delete rule: any Eloquent delete of a GuardsDeletion
 * record is refused while it reports a blocker — from tinker, a job or a future
 * action, not just the admin button. Three layers, one rule: the button explains
 * (AdminActions::guardedDelete()), the model refuses (this), and the database
 * refuses even deleteQuietly() or a raw query (RESTRICT foreign keys, prompt 008).
 */
trait RefusesGuardedDeletion
{
    public static function bootRefusesGuardedDeletion(): void
    {
        static::deleting(function (GuardsDeletion $model): void {
            $reason = $model->deletionBlocker();

            if ($reason !== null) {
                throw new DeletionBlockedException($reason);
            }
        });
    }
}
