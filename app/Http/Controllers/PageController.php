<?php

namespace App\Http\Controllers;

use App\Models\CourseDate;
use App\Settings\GeneralSettings;
use App\Support\SiteContent;
use App\ViewModels\PaymentSuccessPage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PageController extends Controller
{
    public function __construct(private readonly SiteContent $content) {}

    public function home(): View
    {
        // Empty (so the home "Latest News" block hides) when News is switched off.
        $latestNews = app(GeneralSettings::class)->news_enabled
            ? $this->content->latestNews(3)
            : new Collection;

        return view('pages.home', [
            'instructors' => $this->content->instructors(),
            'testimonials' => $this->content->featuredTestimonials(),
            'galleryImages' => $this->content->galleryImages(),
            'services' => $this->content->homeServices(),
            'latestNews' => $latestNews,
        ]);
    }

    public function tandem(): View
    {
        return view('pages.tandem', [
            'product' => $this->content->tandemProduct(),
        ]);
    }

    public function aff(): View
    {
        return view('pages.aff', [
            'products' => $this->content->affProducts(),
            // Live query (not cached): remaining places must always be current.
            'courseDates' => CourseDate::upcomingOpen()->with(['product', 'location'])->get(),
        ]);
    }

    public function coached(): View
    {
        return view('pages.coached');
    }

    public function shop(): View
    {
        return view('pages.shop', [
            'items' => $this->content->shopItems(),
        ]);
    }

    public function testimonials(): View
    {
        return view('pages.testimonials', [
            'testimonials' => $this->content->allTestimonials(),
        ]);
    }

    public function hallOfFame(): View
    {
        return view('pages.hall-of-fame', [
            'entries' => $this->content->hallOfFame(),
        ]);
    }

    public function bookTandem(): View
    {
        return view('pages.book-tandem');
    }

    public function bookAff(): View
    {
        return view('pages.book-aff');
    }

    public function vouchers(): View
    {
        return view('pages.vouchers');
    }

    public function newsletter(): View
    {
        return view('pages.newsletter');
    }

    public function news(Request $request): View
    {
        $perPage = 9;
        $articles = $this->content->publishedNews();
        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginator = new LengthAwarePaginator(
            $articles->forPage($page, $perPage)->values(),
            $articles->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('pages.news.index', ['articles' => $paginator]);
    }

    public function newsArticle(string $slug): View
    {
        $article = $this->content->newsArticle($slug);

        abort_if($article === null, 404);

        // Availability is always live — places-left changes between cache busts.
        $course = $article->course_date_id !== null
            ? CourseDate::with('location')->find($article->course_date_id)
            : null;

        return view('pages.news.show', ['article' => $article, 'course' => $course]);
    }

    /**
     * Stripe redirects here with the Checkout session id, so the page can
     * show the customer their booking (or a processing note while the
     * webhook catches up).
     */
    public function paymentSuccess(Request $request, PaymentSuccessPage $page): View
    {
        return view('pages.payment-success', $page->viewData(
            $request->query('session_id'),
            $request->query('booking'),
        ));
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }
}
