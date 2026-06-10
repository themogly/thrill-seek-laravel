<x-mail::message>
# A jump from 15,000ft, wrapped up 🎁

Hi {{ $voucher->purchaser_name }},

Here it is — **{{ $voucher->formatted_amount }}** towards
{{ $voucher->product->name ?? 'a tandem skydive' }} with G-Force Skydiving@if ($voucher->recipient_name), for **{{ $voucher->recipient_name }}**@endif.

<x-mail::panel>
**Voucher code**

# {{ $voucher->code }}

Valid until {{ $voucher->expires_at->format('j F Y') }}
</x-mail::panel>

@if (filled($voucher->message))
> {{ $voucher->message }}
@endif

**How to redeem:** book online and enter the code at checkout — or reply to
this email and we'll sort everything.

<x-mail::button :url="$bookingUrl">
Book the jump
</x-mail::button>

Blue skies,<br>
The G-Force team
</x-mail::message>
