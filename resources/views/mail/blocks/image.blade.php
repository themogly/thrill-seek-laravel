@php
    use Illuminate\Support\Str;
    $src = $data['image'] ?? '';
    $src = $src ? (Str::startsWith($src, ['http://', 'https://']) ? $src : url($src)) : '';
    $link = $data['link'] ?? '';
    $link = $link ? (Str::startsWith($link, ['http://', 'https://']) ? $link : url($link)) : '';
    $caption = $data['caption'] ?? '';
@endphp
@if ($src)
    <div style="margin:0 0 20px;">
        @if ($link)<a href="{{ $link }}" style="text-decoration:none;">@endif
            <img src="{{ $src }}" alt="{{ $caption }}" width="600" style="display:block;width:100%;max-width:600px;height:auto;border:0;" />
        @if ($link)</a>@endif
        @if ($caption)
            <p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#a1a1aa;text-align:center;">{{ $caption }}</p>
        @endif
    </div>
@endif
