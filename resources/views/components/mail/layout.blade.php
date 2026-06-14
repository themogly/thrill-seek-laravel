@props(['name' => null])
{{-- Shared transactional-email shell: the greeting and sign-off are defined
     ONCE here so every email opens and closes identically (consistency by
     construction). Per-email views provide only the unique body in the slot.
     The sign-off wording is a single CMS value (GeneralSettings::email_signoff),
     so the owner can change it everywhere from one place. Marketing
     (NewsletterCampaign) and admin-facing notifications do not use this. --}}
@php($signoff = app(\App\Settings\GeneralSettings::class)->email_signoff)
<x-mail::message>
@if (filled($name))
Hi {{ $name }},
@endif

{{ $slot }}

{!! nl2br(e($signoff)) !!}
</x-mail::message>
