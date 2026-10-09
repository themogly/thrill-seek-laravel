@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- The brand logo (overrides Laravel's default app-name TEXT header) so every
     transactional email carries the mark. ($slot — the app name — is intentionally
     not shown.) One shared, CID-embedded partial: see App\Support\MailLogo. --}}
@include('mail.partials.logo-img')
</a>
</td>
</tr>
