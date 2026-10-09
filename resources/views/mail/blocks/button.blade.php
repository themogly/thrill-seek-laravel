@php
    use Illuminate\Support\Str;
    $url = $data['url'] ?? '#';
    $url = Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
@endphp
<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 24px;border-collapse:collapse;"><tr>
<td style="background-color:{{ \App\Support\BrandHex::STRONG }};"><a href="{{ $url }}" style="display:inline-block;padding:14px 30px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#ffffff;text-decoration:none;">{{ $data['label'] ?? 'Learn more' }}</a></td>
</tr></table>
