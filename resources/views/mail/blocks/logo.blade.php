{{-- Brand logo header. The NEW vector logo, exported to PNG (email clients don't render
     SVG — Outlook/Gmail won't). ABSOLUTE URL (`url()` → APP_URL-based, never a relative
     asset path, which email clients can't resolve). Explicit width/height + alt, centred
     on the white card. Asset is 360×136; shown at 180×68 (2× for retina). NAVY mark on a
     transparent background, so it reads on the light email card. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 24px;border-collapse:collapse;">
    <tr>
        <td align="center" style="padding:4px 0 12px;">
            <a href="{{ url('/') }}" style="text-decoration:none;">
                <img src="{{ url('/images/email/logo.png') }}" width="180" height="68" alt="G-Force Skydiving" style="display:block;border:0;outline:none;text-decoration:none;width:180px;height:68px;" />
            </a>
        </td>
    </tr>
</table>
