<?php

namespace Tests\Feature\Account;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Testimonial;
use App\Support\SiteContent;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    private function customerWithCompletedBooking(): Customer
    {
        $customer = Customer::factory()->create();
        Booking::factory()->create(['customer_id' => $customer->id, 'status' => BookingStatus::Completed]);

        return $customer;
    }

    public function test_only_customers_with_a_completed_booking_can_review(): void
    {
        $eligible = $this->customerWithCompletedBooking();
        $this->actingAs($eligible, 'customer')->get('/account/review')->assertOk()->assertSee('Submit review');

        $ineligible = Customer::factory()->create();
        Booking::factory()->create(['customer_id' => $ineligible->id, 'status' => BookingStatus::Confirmed]);
        $this->actingAs($ineligible, 'customer')->get('/account/review')->assertForbidden();
    }

    public function test_a_submitted_review_starts_unapproved_and_is_not_public(): void
    {
        $customer = $this->customerWithCompletedBooking();

        $this->actingAs($customer, 'customer')->post('/account/review', [
            'rating' => 5,
            'quote' => 'Absolutely the best day of my life — the team were incredible.',
        ])->assertRedirect('/account');

        $review = Testimonial::where('customer_id', $customer->id)->sole();
        $this->assertFalse($review->approved);
        $this->assertSame(5, $review->rating);

        // Not shown publicly while unapproved.
        $this->assertFalse(app(SiteContent::class)->allTestimonials()->contains('id', $review->id));
    }

    public function test_approving_a_review_surfaces_it_publicly(): void
    {
        $customer = $this->customerWithCompletedBooking();
        $review = Testimonial::factory()->unapproved()->create(['customer_id' => $customer->id, 'quote' => 'Once approved I am visible.']);

        $this->assertFalse(app(SiteContent::class)->allTestimonials()->contains('id', $review->id));

        $review->update(['approved' => true]); // admin moderation; observer busts the cache

        $this->assertTrue(app(SiteContent::class)->allTestimonials()->contains('id', $review->id));
    }

    public function test_ineligible_customer_cannot_post_a_review(): void
    {
        $customer = Customer::factory()->create(); // no completed booking

        $this->actingAs($customer, 'customer')
            ->post('/account/review', ['rating' => 5, 'quote' => 'Trying to sneak a review in here.'])
            ->assertForbidden();

        $this->assertSame(0, Testimonial::where('customer_id', $customer->id)->count());
    }
}
