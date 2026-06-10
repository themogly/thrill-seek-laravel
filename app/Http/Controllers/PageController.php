<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\GalleryImage;
use App\Models\HallOfFameEntry;
use App\Models\Instructor;
use App\Models\Product;
use App\Models\ShopItem;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'instructors' => Instructor::ordered()->get(),
            'testimonials' => Testimonial::featured()->ordered()->limit(3)->get(),
            'galleryImages' => GalleryImage::ordered()->get(),
            'services' => Product::active()->where('featured_on_home', true)->ordered()->get(),
        ]);
    }

    public function tandem(): View
    {
        return view('pages.tandem', [
            'product' => Product::active()->ofType(ProductType::Tandem)->ordered()->with('addOns')->first(),
        ]);
    }

    public function aff(): View
    {
        return view('pages.aff', [
            'products' => Product::active()->ofType(ProductType::Aff)->ordered()->get(),
        ]);
    }

    public function coached(): View
    {
        return view('pages.coached');
    }

    public function shop(): View
    {
        return view('pages.shop', [
            'items' => ShopItem::ordered()->get(),
        ]);
    }

    public function testimonials(): View
    {
        return view('pages.testimonials', [
            'testimonials' => Testimonial::ordered()->get(),
        ]);
    }

    public function hallOfFame(): View
    {
        return view('pages.hall-of-fame', [
            'entries' => HallOfFameEntry::ordered()->get(),
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
