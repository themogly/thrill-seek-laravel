@php
    // Dynamic: a chosen AFF course (or the next open one), resolved live at render.
    $id = $data['course_date_id'] ?? null;
    $course = $id
        ? \App\Models\CourseDate::with('location')->find($id)
        : \App\Models\CourseDate::upcomingOpen()->with('location')->first();
@endphp
@if ($course)
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:0 0 24px;border-collapse:collapse;background-color:#0a0f23;"><tr>
        <td style="padding:24px;">
            <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:2px;color:{{ \App\Support\BrandHex::ACCENT }};">Featured AFF course</p>
            <h3 style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-weight:bold;text-transform:uppercase;color:#ffffff;font-size:22px;">{{ $course->date_range_label }}</h3>
            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#cbd5e1;">{{ $course->location->name }} &middot; {{ $course->remaining_places }} {{ \Illuminate\Support\Str::plural('place', $course->remaining_places) }} left &middot; {{ $course->formatted_deposit }} deposit</p>
            @include('mail.blocks.button', ['data' => ['label' => 'Book this course', 'url' => '/book/aff?course='.$course->id]])
        </td>
    </tr></table>
@endif
