<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * The admin "Forgot password?" page (prompt 027). Filament's page says "We can't
 * find a user with that email address" for an unknown email, which would tell
 * anyone which address is the admin login. Here an unknown address gets exactly
 * the notice a real one gets; the throttle message is unchanged. Lives outside
 * app/Filament/Pages so page discovery doesn't put it in the navigation.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected function getFailureNotification(string $status): ?Notification
    {
        if ($status === Password::INVALID_USER) {
            return $this->getSentNotification(Password::RESET_LINK_SENT);
        }

        return parent::getFailureNotification($status);
    }
}
