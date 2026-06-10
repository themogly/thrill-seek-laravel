<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Gift voucher {{ $voucher->code }}</title>
<style>
    /* dompdf: A5 landscape gift voucher in the Round 5B editorial language —
       flat navy, sharp corners, rules, condensed uppercase type. Helvetica is
       the closest bundled face; the display look comes from weight/tracking. */
    @page { margin: 0; }
    body { margin: 0; font-family: Helvetica, sans-serif; color: #0c1330; }
    .band { background: #0a0f23; color: #ffffff; padding: 22px 40px 16px 40px; border-bottom: 5px solid #2f8de4; }
    .eyebrow { font-size: 11px; letter-spacing: 5px; text-transform: uppercase; color: #0ea5e9; font-weight: bold; margin: 0 0 10px 0; }
    h1 { font-size: 34px; line-height: 0.95; text-transform: uppercase; letter-spacing: 1px; margin: 0; font-weight: bold; }
    h1 .accent { color: #0ea5e9; }
    .body { padding: 18px 40px 0 40px; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; }
    .label { font-size: 9px; letter-spacing: 3px; text-transform: uppercase; color: #5a6275; font-weight: bold; padding-bottom: 4px; }
    .value { font-size: 14px; font-weight: bold; color: #0c1330; padding-bottom: 10px; }
    .code-box { border: 3px solid #2f8de4; padding: 10px 16px; text-align: center; }
    .code-box .label { color: #2f8de4; }
    .code { font-size: 24px; font-weight: bold; letter-spacing: 4px; color: #0c1330; }
    .amount { font-size: 34px; font-weight: bold; color: #2f8de4; line-height: 1; }
    .message { border-left: 4px solid #2f8de4; padding: 8px 12px; font-size: 11px; color: #333a4f; font-style: italic; }
    .foot { border-top: 2px solid #e2e6ef; margin-top: 8px; padding: 10px 40px; font-size: 8px; color: #5a6275; letter-spacing: 0.5px; }
    .foot strong { color: #0c1330; text-transform: uppercase; letter-spacing: 2px; }
</style>
</head>
<body>
    <div class="band">
        <p class="eyebrow">{{ $general->site_name }} — gift voucher</p>
        <h1>One life.<br /><span class="accent">One adventure.</span><br />Live it.</h1>
    </div>

    <div class="body">
        <table>
            <tr>
                <td style="width: 55%; padding-right: 30px;">
                    @if ($voucher->recipient_name)
                        <p class="label">For</p>
                        <p class="value">{{ $voucher->recipient_name }}</p>
                    @endif
                    <p class="label">From</p>
                    <p class="value">{{ $voucher->purchaser_name }}</p>
                    @if ($voucher->message)
                        <p class="message">&ldquo;{{ $voucher->message }}&rdquo;</p>
                    @endif
                </td>
                <td style="width: 45%;">
                    <p class="amount">{{ $voucher->formatted_amount }}</p>
                    <p class="label" style="padding-top: 6px;">towards {{ $voucher->product?->name ?? 'a skydive' }}</p>
                    <div class="code-box" style="margin-top: 10px;">
                        <p class="label">Voucher code</p>
                        <p class="code">{{ $voucher->code }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="foot">
        <strong>How to redeem:</strong> book online at {{ $bookingUrl }} and enter the code at checkout, or call {{ $general->phone }}.
        Valid until {{ $voucher->expires_at?->format('j F Y') }} &middot; transferable &middot; non-refundable.
    </div>
</body>
</html>
