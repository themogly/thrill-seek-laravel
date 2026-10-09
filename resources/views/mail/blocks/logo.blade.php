{{-- Brand logo header block for newsletters, centred on the white card. The image is
     the ONE shared, CID-embedded logo partial (App\Support\MailLogo). --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 24px;border-collapse:collapse;">
    <tr>
        <td align="center" style="padding:4px 0 12px;">
            <a href="{{ url('/') }}" style="text-decoration:none;">
                @include('mail.partials.logo-img')
            </a>
        </td>
    </tr>
</table>
