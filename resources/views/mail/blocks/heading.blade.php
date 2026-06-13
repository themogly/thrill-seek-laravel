@php $size = ($data['level'] ?? 'h2') === 'h1' ? '30px' : '22px'; @endphp
<h2 style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-weight:bold;text-transform:uppercase;letter-spacing:0.5px;color:#0a0f23;font-size:{{ $size }};line-height:1.2;">{{ $data['text'] ?? '' }}</h2>
