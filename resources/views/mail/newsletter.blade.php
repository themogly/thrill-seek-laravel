<x-mail::message>
@if (! empty($preheader))
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;line-height:0;font-size:0;">{{ $preheader }}</div>
@endif
{!! $body !!}

Blue skies,
The G-Force team

<x-slot:subcopy>
You're receiving this because you confirmed your subscription to the G-Force
Skydiving newsletter. [Unsubscribe instantly]({{ $unsubscribeUrl }}) — one click,
no login.
</x-slot:subcopy>
</x-mail::message>
