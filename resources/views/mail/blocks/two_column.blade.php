@php
    use Illuminate\Support\Str;
    $img = $data['image'] ?? '';
    $img = $img ? (Str::startsWith($img, ['http://', 'https://']) ? $img : url($img)) : '';
    $side = $data['image_side'] ?? 'left';
    $bUrl = $data['button_url'] ?? '';
    $bUrl = $bUrl ? (Str::startsWith($bUrl, ['http://', 'https://']) ? $bUrl : url($bUrl)) : '';
@endphp
{{-- Fluid-hybrid: inline-block columns wrap to stack on narrow screens, no media query. --}}
<div style="margin:0 0 24px;font-size:0;text-align:left;">
    @if ($side === 'left' && $img)
        <div style="display:inline-block;width:100%;max-width:270px;vertical-align:top;">
            <img src="{{ $img }}" width="270" alt="" style="display:block;width:100%;max-width:270px;height:auto;border:0;" />
        </div>
    @endif
    <div style="display:inline-block;width:100%;max-width:290px;vertical-align:top;padding:0 12px;box-sizing:border-box;">
        @if (! empty($data['heading']))
            <h3 style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-weight:bold;text-transform:uppercase;color:#0a0f23;font-size:18px;">{{ $data['heading'] }}</h3>
        @endif
        <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#52525b;">{{ $data['text'] ?? '' }}</p>
        @if (! empty($data['button_label']) && $bUrl)
            <a href="{{ $bUrl }}" style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#2f8de4;text-decoration:none;">{{ $data['button_label'] }} &rarr;</a>
        @endif
    </div>
    @if ($side !== 'left' && $img)
        <div style="display:inline-block;width:100%;max-width:270px;vertical-align:top;">
            <img src="{{ $img }}" width="270" alt="" style="display:block;width:100%;max-width:270px;height:auto;border:0;" />
        </div>
    @endif
</div>
