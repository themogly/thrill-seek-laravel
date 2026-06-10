<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/tandem', [PageController::class, 'tandem'])->name('tandem');
Route::get('/aff', [PageController::class, 'aff'])->name('aff');
Route::get('/coached', [PageController::class, 'coached'])->name('coached');
Route::get('/shop', [PageController::class, 'shop'])->name('shop');
Route::get('/testimonials', [PageController::class, 'testimonials'])->name('testimonials');
Route::get('/hall-of-fame', [PageController::class, 'hallOfFame'])->name('hall-of-fame');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/book/tandem', [PageController::class, 'bookTandem'])->name('book.tandem');
Route::get('/book/aff', [PageController::class, 'bookAff'])->name('book.aff');

Route::get('/payment/success', [PageController::class, 'paymentSuccess'])->name('payment.success');
Route::view('/payment/cancelled', 'pages.payment-cancelled')->name('payment.cancelled');

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('/sitemap.xml', function () {
    $entries = [
        ['path' => '/', 'priority' => '1.0'],
        ['path' => '/tandem', 'priority' => '0.9'],
        ['path' => '/aff', 'priority' => '0.9'],
        ['path' => '/book/tandem', 'priority' => '0.9'],
        ['path' => '/book/aff', 'priority' => '0.9'],
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
