<?php

namespace App\Contracts;

/**
 * A record that money or other records hang off. It says, in plain English, why
 * it can't be deleted right now — or null when deleting is safe. The admin's
 * Delete button reads this through AdminActions::guardedDelete(), which disables
 * the button (Filament then refuses the action server-side) and shows the reason.
 */
interface GuardsDeletion
{
    public function deletionBlocker(): ?string;
}
