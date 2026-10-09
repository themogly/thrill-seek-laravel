<?php

namespace Tests\Feature\Testimonials;

use App\Models\Customer;
use App\Models\Testimonial;
use App\Support\StructuredData;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestimonialSeeder;
use Tests\TestCase;

/**
 * Sample reviews never reach a server, and the published review rating only
 * ever counts real customers' reviews — enough of them to mean something.
 */
class HonestRatingTest extends TestCase
{
    public function test_seeding_a_server_creates_no_testimonials(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(0, Testimonial::count());
    }

    public function test_local_seeding_still_fills_the_page_for_development(): void
    {
        app()->detectEnvironment(fn (): string => 'local');

        $this->seed(TestimonialSeeder::class);

        $this->assertSame(8, Testimonial::approved()->count());
    }

    public function test_samples_and_owner_typed_reviews_never_produce_a_rating(): void
    {
        $samples = Testimonial::factory()->count(8)->create(['customer_id' => null]);

        $this->assertNull(StructuredData::aggregateRating($samples));
    }

    public function test_a_rating_needs_at_least_three_real_customer_reviews(): void
    {
        $two = Testimonial::factory()->count(2)->create(['customer_id' => fn () => Customer::factory()]);
        $this->assertNull(StructuredData::aggregateRating($two));

        $three = $two->push(Testimonial::factory()->create(['customer_id' => Customer::factory()]));
        $this->assertSame(3, StructuredData::aggregateRating($three)['aggregateRating']['reviewCount']);
    }

    public function test_review_count_matches_the_reviews_shown_on_the_page(): void
    {
        Testimonial::factory()->count(4)->create(['customer_id' => fn () => Customer::factory()]);

        $html = (string) $this->get('/testimonials')->assertOk()->getContent();

        preg_match('/"reviewCount":(\d+)/', $html, $count);
        $this->assertNotEmpty($count, 'No AggregateRating rendered.');
        // Same collection feeds both: compare the two figures with each other.
        $this->assertSame(substr_count($html, '<cite'), (int) $count[1]);
    }

    public function test_pages_render_an_intentional_absence_with_no_testimonials(): void
    {
        $home = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsStringIgnoringCase('Voices from the Sky', $home, 'Orphaned testimonials heading on the homepage.');

        $this->get('/testimonials')
            ->assertOk()
            ->assertSee('No reviews yet')
            ->assertSee(route('account.login'), false)
            ->assertDontSee('AggregateRating');
    }
}
