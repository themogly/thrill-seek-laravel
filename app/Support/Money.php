<?php

namespace App\Support;

/**
 * All money in this app is stored as integer pence (GBP).
 */
final class Money
{
    public static function formatPence(int $pence): string
    {
        $pounds = intdiv($pence, 100);
        $remainder = $pence % 100;

        if ($remainder === 0) {
            return '£'.number_format($pounds);
        }

        return '£'.number_format($pence / 100, 2);
    }
}
