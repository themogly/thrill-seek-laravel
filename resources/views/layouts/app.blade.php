@inject('general', 'App\Settings\GeneralSettings')
@php
    // One place computes the page's meta; pages override via @section (title,
    // description, og_image, og_type, robots). og:title/description and the
    // twitter tags follow the page title/description automatically, so a page
    // only sets what's unique. Images are made absolute (required by crawlers).
    $defaultTitle = $general->seo_title;
    $defaultDescription = $general->seo_description;
    $defaultOgImage = \Illuminate\Support\Str::startsWith($general->og_image, ['http://', 'https://'])
        ? $general->og_image
        : url($general->og_image);

    // Blade's startSection() already HTML-escapes inline @section content, so
    // these values are escaped once; defaults are e()'d to match, and they're
    // echoed raw below with {!! !!} (never double-escaped).
    $pageTitle = trim($__env->yieldContent('title', e($defaultTitle)));
    $pageDescription = trim($__env->yieldContent('description', e($defaultDescription)));
    $pageOgImage = trim($__env->yieldContent('og_image', e($defaultOgImage)));
    // OG copy follows the page title/description unless a page sets its own.
    $pageOgTitle = trim($__env->yieldContent('og_title', $pageTitle));
    $pageOgDescription = trim($__env->yieldContent('og_description', $pageDescription));
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="{!! $pageDescription !!}" />
    <link rel="canonical" href="{{ $canonical }}" />
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')" />
    @endif

    {{-- Open Graph --}}
    <meta property="og:site_name" content="{{ $general->site_name }}" />
    <meta property="og:locale" content="en_GB" />
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:url" content="{{ $canonical }}" />
    <meta property="og:title" content="{!! $pageOgTitle !!}" />
    <meta property="og:description" content="{!! $pageOgDescription !!}" />
    <meta property="og:image" content="{!! $pageOgImage !!}" />

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{!! $pageTitle !!}" />
    <meta name="twitter:description" content="{!! $pageDescription !!}" />
    <meta name="twitter:image" content="{!! $pageOgImage !!}" />

    @stack('head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('json-ld')
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
