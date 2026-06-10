<x-mail::message>
Hi {{ $recipientName }},

{!! nl2br(e($body)) !!}

---

Your course: **{{ $course->date_range_label }} — {{ $course->location->name }}**

Questions? Just reply to this email.

Blue skies,
The G-Force team
</x-mail::message>
