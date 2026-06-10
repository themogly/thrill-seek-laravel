<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Email previews (local only)</title></head>
<body style="font-family: sans-serif; padding: 2rem">
    <h1>Email previews</h1>
    <ul>
        @foreach ($keys as $key)
            <li><a href="{{ route('dev.mail.show', $key) }}">{{ $key }}</a></li>
        @endforeach
    </ul>
</body>
</html>
