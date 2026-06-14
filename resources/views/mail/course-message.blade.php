<x-mail.layout :name="$recipientName">
{!! nl2br(e($body)) !!}

---

Your course: **{{ $course->date_range_label }} — {{ $course->location->name }}**

Questions? Just reply to this email.
</x-mail.layout>
