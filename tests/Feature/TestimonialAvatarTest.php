<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Tests\TestCase;

class TestimonialAvatarTest extends TestCase
{
    public function test_avatar_url_resolves_bundled_and_uploaded_paths(): void
    {
        $bundled = Testimonial::factory()->make(['avatar' => '/images/instructors/ren.jpg']);
        $this->assertSame('/images/instructors/ren.jpg', $bundled->avatar_url);

        $uploaded = Testimonial::factory()->make(['avatar' => 'testimonials/abc.webp']);
        $this->assertStringContainsString('testimonials/abc.webp', (string) $uploaded->avatar_url);

        $none = Testimonial::factory()->make(['avatar' => null]);
        $this->assertNull($none->avatar_url);
    }

    public function test_photo_url_resolves_bundled_and_uploaded_paths(): void
    {
        $bundled = Testimonial::factory()->make(['photo' => '/images/tandem.jpg']);
        $this->assertSame('/images/tandem.jpg', $bundled->photo_url);

        $uploaded = Testimonial::factory()->make(['photo' => 'testimonials-photos/abc.webp']);
        $this->assertStringContainsString('testimonials-photos/abc.webp', (string) $uploaded->photo_url);

        $this->assertNull(Testimonial::factory()->make(['photo' => null])->photo_url);
    }

    public function test_featured_testimonial_with_photo_renders_as_the_hero_feature(): void
    {
        Testimonial::factory()->featured()->withPhoto()->create(['name' => 'Ada Avatar', 'quote' => 'Loved every second of it.']);

        $this->get('/testimonials')
            ->assertOk()
            ->assertSee('/images/tandem.jpg', false)
            ->assertSee('Ada Avatar');
    }

    public function test_grid_testimonial_without_a_photo_uses_the_navy_monogram(): void
    {
        // A non-featured, photo-less testimonial appears in the grid as a navy
        // monogram block (the intentional fallback), not a broken image.
        Testimonial::factory()->create(['name' => 'Zoltan Q.', 'avatar' => null, 'photo' => null, 'featured' => false, 'quote' => 'Great.']);

        $response = $this->get('/testimonials')->assertOk();
        $response->assertSee('Zoltan Q.');
        $response->assertSee('band-ink absolute', false);
    }
}
