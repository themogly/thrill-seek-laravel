@inject('settings', 'App\Settings\GeneralSettings')
{{-- Branded, email-safe footer band — navy with the wordmark, social links, the
     signed one-click unsubscribe line and copyright. Palette matches the on-navy
     blocks (#ffffff / #cbd5e1 / #2f8de4). Table-based, inline-styled, no flexbox. --}}
<tr>
    <td class="gf-pad" style="padding:0 40px;">
        <hr style="border:0;border-top:1px solid #e4e4e7;margin:0 0 4px;height:1px;line-height:1px;" />
    </td>
</tr>
<tr>
    <td class="gf-pad" style="padding:8px 40px 28px;font-family:Arial,Helvetica,sans-serif;">
        <p style="margin:0 0 2px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#52525b;">Blue skies,</p>
        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#0a0f23;font-weight:bold;">The {{ $settings->site_name }} team</p>
    </td>
</tr>
<tr>
    <td class="gf-pad" align="center" style="padding:32px 40px;background-color:#0a0f23;font-family:Arial,Helvetica,sans-serif;">
        <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;text-transform:uppercase;letter-spacing:2px;color:#ffffff;">{{ $settings->site_name }}</p>
        <p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#cbd5e1;">{{ $settings->tagline }}</p>

        <p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;">
            @if (! empty($settings->instagram_url))<a href="{{ $settings->instagram_url }}" style="color:#2f8de4;text-decoration:none;">Instagram</a>@endif
            @if (! empty($settings->instagram_url) && ! empty($settings->facebook_url))<span style="color:#cbd5e1;">&nbsp;&middot;&nbsp;</span>@endif
            @if (! empty($settings->facebook_url))<a href="{{ $settings->facebook_url }}" style="color:#2f8de4;text-decoration:none;">Facebook</a>@endif
        </p>

        <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#cbd5e1;">
            You're receiving this because you confirmed your subscription to the {{ $settings->site_name }} newsletter.<br>
            <a href="{{ $unsubscribeUrl }}" style="color:#ffffff;text-decoration:underline;">Unsubscribe instantly</a> — one click, no login.
        </p>
        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#cbd5e1;">{{ $settings->footer_copyright }}</p>
    </td>
</tr>
