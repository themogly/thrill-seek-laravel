<x-mail::message>
{!! nl2br(e($body)) !!}

Blue skies,
The G-Force team

<x-slot:subcopy>
You're receiving this because you confirmed your subscription to the G-Force
Skydiving newsletter. [Unsubscribe instantly]({{ $unsubscribeUrl }}) — one click,
no login.
</x-slot:subcopy>
</x-mail::message>
