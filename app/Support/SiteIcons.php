<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The lucide icon names available to the public site's <x-icon> component
 * (see resources/views/components/icon.blade.php). Used to build icon
 * pickers in the admin panel.
 */
final class SiteIcons
{
    /** @return array<string, string> icon name => human label */
    public static function options(): array
    {
        $names = [
            'arrow-right', 'award', 'calendar-check', 'check', 'chevron-down',
            'facebook', 'graduation-cap', 'instagram', 'mail', 'map-pin',
            'medal', 'menu', 'phone', 'plane', 'quote', 'send', 'shield',
            'shield-check', 'shopping-bag', 'sparkles', 'target', 'trophy',
            'users', 'x',
        ];

        return array_combine($names, array_map(
            fn (string $name): string => ucwords(str_replace('-', ' ', $name)),
            $names,
        ));
    }
}
