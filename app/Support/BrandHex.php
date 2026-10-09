<?php

namespace App\Support;

/**
 * The brand blues as hex, for the two media that can't read app.css's tokens:
 * emails (inline styles, mail clients) and PDFs (dompdf). Defined once here;
 * the static mail theme `vendor/mail/html/themes/gforce.css` can't call PHP, so
 * EmailColourTest pins it to these values.
 *
 * Same rule as the site (016/023): STRONG for anything read on a light surface
 * and every fill under text; ACCENT for borders and rules, for text on the navy
 * band (where STRONG would fail), and for large display figures (≥24px, 3:1).
 */
class BrandHex
{
    /** = `--primary-strong` oklch(0.545 0.18 240): 4.61:1 on white, and white on it. */
    public const STRONG = '#0078cc';

    /** The emails' bright accent: 3.47:1 on white (decoration only there), 5.47:1 on the navy band. */
    public const ACCENT = '#2f8de4';

    /** The emails' navy band and dark text. */
    public const NAVY = '#0a0f23';
}
