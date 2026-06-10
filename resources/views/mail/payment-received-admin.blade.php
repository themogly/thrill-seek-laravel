<x-mail::message>
# Payment received

**{{ $payment->formatted_amount }}** ({{ $payment->purpose->getLabel() }}, {{ $payment->method->getLabel() }})
from **{{ $booking->name }}** ({{ $booking->email }}).

@if ($booking->product)
**Product:** {{ $booking->product->name }}
@endif
**Booking reference:** {{ $booking->reference }}
**Outstanding balance:** {{ $booking->formatted_balance_due }}

<x-mail::button :url="url('/admin/bookings/'.$booking->id.'/edit')">
Open booking
</x-mail::button>
</x-mail::message>
