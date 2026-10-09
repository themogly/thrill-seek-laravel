<?php

namespace App\Support;

/**
 * The brand logo in emails travels INSIDE the message as an inline (CID) PNG —
 * never hot-linked from APP_URL: a staging site behind basic-auth 401s the image
 * and many clients block remote images. Every email's logo is the one partial
 * `mail.partials.logo-img`, which always points at `cid:{CID}`; the
 * EmbedsMailLogo trait attaches the PNG under that name when the message is
 * actually sent. Previews (admin builder, /dev/mail) have no message to embed
 * into, so render() swaps the cid for the public URL.
 */
final class MailLogo
{
    public const CID = 'gforce-logo';

    /** PNG (clients don't render SVG); 360×136, shown at 180×68. Kept out of image optimisation. */
    public static function path(): string
    {
        return public_path('images/email/logo.png');
    }

    public static function src(): string
    {
        return 'cid:'.self::CID;
    }

    public static function forPreview(string $html): string
    {
        return str_replace(self::src(), url('/images/email/logo.png'), $html);
    }
}
