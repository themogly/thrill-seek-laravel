@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #18181b; font-size: 12px; margin: 0; padding: 40px; }
        h1 { color: #0a0f23; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 2px; }
        .muted { color: #71717a; }
        .band { background: #0a0f23; color: #fff; padding: 16px 20px; margin: 0 0 24px; }
        .band h1 { color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        .rows td { padding: 8px 0; border-bottom: 1px solid #e4e4e7; }
        .total td { padding: 10px 0; border-top: 2px solid #0a0f23; font-weight: bold; }
        .right { text-align: right; }
        .label { text-transform: uppercase; letter-spacing: 1px; font-size: 10px; color: {{ \App\Support\BrandHex::STRONG }}; font-weight: bold; }
    </style>
</head>
<body>
    <div class="band">
        <h1>{{ $general->site_name }}</h1>
        <div>Booking confirmation &amp; receipt</div>
    </div>

    <table style="margin-bottom: 24px;">
        <tr>
            <td style="vertical-align: top;">
                <div class="label">Booking</div>
                <div style="font-size: 16px; font-weight: bold;">{{ $booking->product?->name ?? 'Skydive' }}</div>
                <div class="muted">Reference {{ $booking->reference }}</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="label">Customer</div>
                <div>{{ $booking->name }}</div>
                <div class="muted">{{ $booking->email }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-bottom: 24px;">
        <tr>
            <td class="label">Date</td>
            <td class="right">{{ $booking->scheduled_at?->format('l j F Y, g:ia') ?? 'To be confirmed' }}</td>
        </tr>
        @if ($booking->locationName())
            <tr>
                <td class="label">Location</td>
                <td class="right">{{ $booking->locationName() }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Status</td>
            <td class="right">{{ $booking->status->getLabel() }}</td>
        </tr>
    </table>

    <div class="label" style="margin-bottom: 6px;">Payments</div>
    <table class="rows">
        @forelse ($paidPayments as $payment)
            <tr>
                <td>{{ $payment->paid_at?->format('j M Y') ?? '—' }}</td>
                <td>{{ $payment->purpose->getLabel() }}</td>
                <td class="right">{{ $payment->formatted_amount }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">No payments recorded yet.</td></tr>
        @endforelse
        <tr class="total">
            <td>Total paid</td>
            <td></td>
            <td class="right">{{ Money::formatPence($booking->total_paid_pence) }}</td>
        </tr>
        @if ($booking->hasOutstandingBalance())
            <tr class="total" style="border-top: 0;">
                <td>Balance outstanding</td>
                <td></td>
                <td class="right">{{ $booking->formatted_balance_due }}</td>
            </tr>
        @endif
    </table>

    <p class="muted" style="margin-top: 32px;">Thank you for booking with {{ $general->site_name }}. Blue skies!</p>
</body>
</html>
