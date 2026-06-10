@inject('general', 'App\Settings\GeneralSettings')
@php
    $defaultTitle = $general->seo_title;
    $defaultDescription = $general->seo_description;
    $ogImage = $general->og_image;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    {{-- Mirrors the original's meta merge: <title>/description come from the page
         (falling back to root defaults), while og:*/twitter:* default to the root
         marketing constants and are only overridden where the source did (tandem
         overrides og:title/og:description via @section). --}}
    <title>@yield('title', $defaultTitle)</title>
    <meta name="description" content="@yield('description', $defaultDescription)" />
    <meta property="og:title" content="@yield('og_title', $defaultTitle)" />
    <meta property="og:description" content="@yield('og_description', $defaultDescription)" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $defaultTitle }}" />
    <meta name="twitter:description" content="{{ $defaultDescription }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="flex min-h-screen flex-col">
        <x-site.header />
        <main class="flex-1">@yield('content')</main>
        <x-site.footer />
    </div>
    <x-ui.toaster />
    @livewireScripts
</body>
</html>
