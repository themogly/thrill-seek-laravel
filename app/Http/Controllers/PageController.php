<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SiteContent;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(private readonly SiteContent $content) {}

    public function home(): View
    {
        return view('pages.home', [
            'instructors' => $this->content->instructors(),
            'testimonials' => $this->content->featuredTestimonials(),
            'galleryImages' => $this->content->galleryImages(),
            'services' => $this->content->homeServices(),
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
