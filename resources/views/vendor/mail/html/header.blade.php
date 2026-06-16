@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- The brand logo as a PNG via an ABSOLUTE URL — overrides Laravel's default
     app-name TEXT header so every transactional email (confirmations, reminders,
     enquiry acks/replies, vouchers, booking emails…) carries the new mark. Email
     clients don't render SVG and can't resolve relative paths, hence PNG + url().
     360×136 asset shown at 180×68 (2× retina); navy on transparent for the light card.
     ($slot — the app name — is intentionally not shown.) --}}
<img src="{{ url('/images/email/logo.png') }}" width="180" height="68" alt="G-Force Skydiving" style="display:block;border:0;outline:none;text-decoration:none;width:180px;height:68px;" />
</a>
</td>
</tr>
