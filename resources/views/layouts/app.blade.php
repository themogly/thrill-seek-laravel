@php
    $defaultTitle = 'G-Force Skydiving — One Life. One Adventure. Live It.';
    $defaultDescription = 'Tandem skydives, AFF courses and advanced coaching in the UK and Spain. Military-trained, BS & USPA certified instructors.';
    $ogImage = 'https://pub-bb2e103a32db4e198524a2e9ed8f35b4.r2.dev/390eabd9-2dea-4686-bffb-bbf2b761eef3/id-preview-c55e11e8--05e69f77-3e5f-46a1-8301-364b0e237a36.lovable.app-1779138654806.png';
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
