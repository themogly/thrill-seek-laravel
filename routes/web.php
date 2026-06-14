<?php

use App\Http\Controllers\Account\BookingController as AccountBookingController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\LoginController as AccountLoginController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ResendWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Models\NewsArticle;
use App\Settings\GeneralSettings;
use App\Support\SiteContent;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/tandem', [PageController::class, 'tandem'])->name('tandem');
Route::get('/aff', [PageController::class, 'aff'])->name('aff');
Route::get('/coached', [PageController::class, 'coached'])->name('coached');
Route::get('/shop', [PageController::class, 'shop'])->middleware('feature:shop')->name('shop');
Route::middleware('feature:news')->group(function (): void {
    Route::get('/news', [PageController::class, 'news'])->name('news');
    Route::get('/news/{slug}', [PageController::class, 'newsArticle'])->name('news.show');
});
Route::get('/testimonials', [PageController::class, 'testimonials'])->name('testimonials');
Route::get('/hall-of-fame', [PageController::class, 'hallOfFame'])->name('hall-of-fame');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/book/tandem', [PageController::class, 'bookTandem'])->name('book.tandem');
Route::get('/book/aff', [PageController::class, 'bookAff'])->name('book.aff');
Route::get('/vouchers', [PageController::class, 'vouchers'])->name('vouchers');

Route::get('/newsletter', [PageController::class, 'newsletter'])->name('newsletter');
Route::get('/newsletter/confirm/{subscriber}', [NewsletterController::class, 'confirm'])
    ->middleware('signed')->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('signed')->name('newsletter.unsubscribe');

Route::get('/payment/success', [PageController::class, 'paymentSuccess'])->name('payment.success');
Route::view('/payment/cancelled', 'pages.payment-cancelled')->name('payment.cancelled');

// Customer account area — passwordless magic-link auth (the `customer` guard).
Route::prefix('account')->name('account.')->group(function (): void {
    Route::get('/login', [AccountLoginController::class, 'show'])->name('login');
    Route::post('/login', [AccountLoginController::class, 'sendLink'])->middleware('throttle:6,1')->name('login.send');
    Route::get('/login/{token}', [AccountLoginController::class, 'verify'])->middleware('throttle:10,1')->name('login.verify');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/', AccountDashboardController::class)->name('dashboard');
        Route::post('/logout', [AccountLoginController::class, 'logout'])->name('logout');

        Route::get('/bookings', [AccountBookingController::class, 'index'])->name('bookings');
        Route::get('/bookings/{booking}', [AccountBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/pay', [AccountBookingController::class, 'pay'])->name('bookings.pay');
    });
});

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
Route::post('/webhooks/resend', ResendWebhookController::class)->name('webhooks.resend');

if (app()->environment('local')) {
    require __DIR__.'/dev.php';
}

Route::get('/robots.txt', function () {
    // Served dynamically so the Sitemap line is an absolute URL on any host.
    $body = implode("\n", [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /admin/',
        'Disallow: /dev/',
        '',
        'Sitemap: '.url('/sitemap.xml'),
        '',
    ]);

    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/sitemap.xml', function () {
    $entries = [
        ['path' => '/', 'priority' => '1.0'],
        ['path' => '/tandem', 'priority' => '0.9'],
        ['path' => '/aff', 'priority' => '0.9'],
        ['path' => '/book/tandem', 'priority' => '0.9'],
        ['path' => '/book/aff', 'priority' => '0.9'],
        ['path' => '/vouchers', 'priority' => '0.8'],
        ['path' => '/coached', 'priority' => '0.8'],
        ['path' => '/testimonials', 'priority' => '0.6'],
        ['path' => '/hall-of-fame', 'priority' => '0.6'],
        ['path' => '/contact', 'priority' => '0.8'],
        ['path' => '/privacy', 'priority' => '0.3'],
        ['path' => '/terms', 'priority' => '0.3'],
    ];

    if (app(GeneralSettings::class)->shop_enabled) {
        $entries[] = ['path' => '/shop', 'priority' => '0.7'];
    }

    if (app(GeneralSettings::class)->news_enabled) {
        $entries[] = ['path' => '/news', 'priority' => '0.6'];

        // Each published article, with its last-modified time so crawlers recrawl
        // on edit. Read through the cached gateway (busted on save).
        foreach (app(SiteContent::class)->publishedNews() as $article) {
            /** @var NewsArticle $article */
            $entries[] = [
                'path' => '/news/'.$article->slug,
                'priority' => '0.5',
                'lastmod' => $article->updated_at?->toAtomString(),
            ];
        }
    }

    $urls = collect($entries)
        ->map(function (array $e): string {
            // Absolute URLs are required in a sitemap.
            $loc = url($e['path']);
            $lastmod = isset($e['lastmod']) ? "<lastmod>{$e['lastmod']}</lastmod>" : '';

            return "  <url><loc>{$loc}</loc>{$lastmod}<changefreq>weekly</changefreq><priority>{$e['priority']}</priority></url>";
        })
        ->implode("\n");

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}\n</urlset>";

    return response($xml, 200, [
        'Content-Type' => 'application/xml',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('sitemap');
