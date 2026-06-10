<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HallOfFameEntry;
use App\Models\Instructor;
use App\Models\Testimonial;
use App\Support\ContentCache;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'instructors' => ContentCache::remember('instructors', fn () => Instructor::ordered()->get()),
            'testimonials' => ContentCache::remember('testimonials.featured', fn () => Testimonial::featured()->ordered()->limit(3)->get()),
        ]);
    }

    public function tandem(): View
    {
        return view('pages.tandem');
    }

    public function aff(): View
    {
        return view('pages.aff');
    }

    public function coached(): View
    {
        return view('pages.coached');
    }

    public function shop(): View
    {
        return view('pages.shop');
    }

    public function testimonials(): View
    {
        return view('pages.testimonials', [
            'testimonials' => ContentCache::remember('testimonials.all', fn () => Testimonial::ordered()->get()),
        ]);
    }

    public function hallOfFame(): View
    {
        return view('pages.hall-of-fame', [
            'entries' => ContentCache::remember('hall_of_fame', fn () => HallOfFameEntry::ordered()->get()),
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
