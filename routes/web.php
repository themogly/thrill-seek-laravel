<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/tandem', 'pages.tandem')->name('tandem');
Route::view('/aff', 'pages.aff')->name('aff');
Route::view('/coached', 'pages.coached')->name('coached');
Route::view('/shop', 'pages.shop')->name('shop');
Route::view('/testimonials', 'pages.testimonials')->name('testimonials');
Route::view('/hall-of-fame', 'pages.hall-of-fame')->name('hall-of-fame');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/terms', 'pages.terms')->name('terms');

Route::get('/sitemap.xml', function () {
    $entries = [
        ['path' => '/', 'priority' => '1.0'],
        ['path' => '/tandem', 'priority' => '0.9'],
        ['path' => '/aff', 'priority' => '0.9'],
        ['path' => '/coached', 'priority' => '0.8'],
        ['path' => '/shop', 'priority' => '0.7'],
        ['path' => '/testimonials', 'priority' => '0.6'],
        ['path' => '/hall-of-fame', 'priority' => '0.6'],
        ['path' => '/contact', 'priority' => '0.8'],
        ['path' => '/privacy', 'priority' => '0.3'],
        ['path' => '/terms', 'priority' => '0.3'],
    ];

    $urls = collect($entries)
        ->map(fn ($e) => "  <url><loc>{$e['path']}</loc><changefreq>weekly</changefreq><priority>{$e['priority']}</priority></url>")
        ->implode("\n");

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}\n</urlset>";

    return response($xml, 200, [
        'Content-Type' => 'application/xml',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('sitemap');
