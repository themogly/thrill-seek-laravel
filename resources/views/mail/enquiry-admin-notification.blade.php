<x-mail::message>
# New enquiry {{ $enquiry->reference }}

**From:** {{ $enquiry->name }} ({{ $enquiry->email }}@if($enquiry->phone), {{ $enquiry->phone }}@endif)

@if ($enquiry->product)
**Product:** {{ $enquiry->product->name }}
@endif
@if ($enquiry->preferred_date)
**Preferred date:** {{ $enquiry->preferred_date->format('j M Y') }}
@endif

**Message:**

{{ $enquiry->messages->first()?->body }}

@if (filled($enquiry->context))
**Details:**

@foreach ($enquiry->context as $key => $value)
- {{ \Illuminate\Support\Str::headline($key) }}: {{ $value }}
@endforeach
@endif

<x-mail::button :url="url('/admin/enquiries/'.$enquiry->id)">
Open in admin
</x-mail::button>

Replying to this email goes straight to the customer.
</x-mail::message>
