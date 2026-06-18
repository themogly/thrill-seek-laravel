<?php

namespace Tests\Feature\Design;

use App\Models\HallOfFameEntry;
use App\Models\Testimonial;
use Tests\TestCase;

class SocialProofTest extends TestCase
{
    public function test_testimonial_stores_rating_and_photo(): void
    {
        $t = Testimonial::factory()->create(['rating' => 5, 'photo' => '/images/tandem.jpg']);

        $this->assertSame(5, $t->refresh()->rating);
        $this->assertSame('/images/tandem.jpg', $t->photo);
    }

    public function test_rating_renders_as_stars_and_omits_cleanly_when_unset(): void
    {
        Testimonial::factory()->featured()->create(['name' => 'Rated Rita', 'rating' => 5, 'quote' => 'Five stars all the way.']);

        $this->get('/testimonials')
            ->assertOk()
            ->assertSee('5 out of 5 stars');

        // A page with only unrated testimonials shows no star rating markup.
        Testimonial::query()->delete();
        Testimonial::factory()->create(['rating' => null]);

        $this->get('/testimonials')->assertOk()->assertDontSee('out of 5 stars');
    }

    public function test_featured_quote_is_shown_in_brand_display_type(): void
    {
        Testimonial::factory()->featured()->withPhoto()->create(['name' => 'Hero Helen', 'quote' => 'The drama of a lifetime.']);

        $this->get('/testimonials')
            ->assertOk()
            // The hero blockquote uses the brand display scale, not grey body copy.
            ->assertSee('font-display text-3xl uppercase', false)
            ->assertSee('The drama of a lifetime.');
    }

    public function test_testimonials_page_renders_at_edge_counts(): void
    {
        // No stranded/orphaned tiles, hero + grid hold at 1, 2 and 7 entries.
        foreach ([1, 2, 7] as $count) {
            Testimonial::query()->delete();
            Testimonial::factory()->count($count)->create();
            Testimonial::query()->first()?->update(['featured' => true]);

            $this->get('/testimonials')->assertOk();
        }
    }

    public function test_hall_of_fame_has_a_photo_hero_and_interactive_tiles(): void
    {
        HallOfFameEntry::factory()->count(5)->create();

        $response = $this->get('/hall-of-fame')->assertOk();

        // Compact photographic hero (image + scrim), not a flat band…
        $response->assertSee('/images/hero-skydive.webp', false);
        // …and tiles carry the shared photo-tile hover zoom.
        $response->assertSee('group-hover:scale-105', false);
    }

    public function test_hall_of_fame_renders_at_odd_count_with_rich_captions(): void
    {
        HallOfFameEntry::factory()->count(3)->create();
        HallOfFameEntry::factory()->create([
            'name' => 'Milestone Max',
            'achieved_on' => '2026-05-01',
            'note' => '500th jump',
        ]);

        $this->get('/hall-of-fame')
            ->assertOk()
            ->assertSee('Milestone Max')
            ->assertSee('500th jump');
    }
}
