<?php

namespace App\Http\Controllers\Account;

use App\Enums\ProductType;
use App\Models\Booking;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends AccountController
{
    public function create(): View
    {
        $customer = $this->customer();
        abort_unless($customer->canLeaveReview(), 403, 'You can leave a review once you have a completed jump with us.');

        return view('account.review', [
            'customer' => $customer,
            'role' => $this->roleFor($customer->latestCompletedBooking()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->customer();
        abort_unless($customer->canLeaveReview(), 403);

        $data = $request->validate([
            'quote' => ['required', 'string', 'min:10', 'max:1000'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('testimonials', 'public')
            : null;

        Testimonial::create([
            'customer_id' => $customer->getKey(),
            'name' => $customer->name,
            'role' => $this->roleFor($customer->latestCompletedBooking()),
            'rating' => $data['rating'],
            'quote' => $data['quote'],
            'photo' => $photoPath,
            'featured' => false,
            'approved' => false, // never public until an admin approves
        ]);

        return redirect()->route('account.dashboard')
            ->with('account_status', 'Thanks for your review! It will appear on our site once our team has approved it.');
    }

    private function roleFor(?Booking $booking): string
    {
        return match ($booking?->product?->type) {
            ProductType::Tandem => 'Tandem Jumper',
            ProductType::Aff => 'AFF Graduate',
            ProductType::Coaching => 'Coached Jumper',
            default => 'Skydiver',
        };
    }
}
