{{-- Plain-Blade, table-based email shell — NOT a Markdown/CommonMark view, so the
     pre-built, inline-styled block HTML in $body is emitted verbatim (no escaping,
     no code-block mangling). ~600px, web-safe fonts, absolute URLs. The mailable
     strips Livewire morph markers from the final string. --}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject }}</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f2f5fa; }
        img { -ms-interpolation-mode: bicubic; }
        a { color: #2f8de4; }
        @media only screen and (max-width: 600px) {
            .gf-container { width: 100% !important; max-width: 100% !important; }
            .gf-pad { padding-left: 24px !important; padding-right: 24px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f2f5fa;">
    @if (! empty($preheader))
        <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;line-height:0;font-size:0;">{{ $preheader }}</div>
    @endif

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background-color:#f2f5fa;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" class="gf-container" border="0" cellpadding="0" cellspacing="0" width="600" style="width:600px;max-width:600px;border-collapse:collapse;background-color:#ffffff;">
                    <tr>
                        <td class="gf-pad" style="padding:36px 40px 8px;font-family:Arial,Helvetica,sans-serif;">
                            {!! $body !!}
                        </td>
                    </tr>
                    @include('mail.newsletter.footer', ['unsubscribeUrl' => $unsubscribeUrl])
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
