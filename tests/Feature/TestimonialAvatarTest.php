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

    public function test_testimonials_page_shows_the_avatar_when_present(): void
    {
        Testimonial::factory()->withAvatar()->create(['name' => 'Ada Avatar', 'quote' => 'Loved it.']);

        $this->get('/testimonials')
            ->assertOk()
            ->assertSee('/images/instructors/ren.jpg', false)
            ->assertSee('Ada Avatar');
    }

    public function test_testimonials_page_falls_back_to_the_initial_when_absent(): void
    {
        Testimonial::factory()->create(['name' => 'Zoltan Q.', 'avatar' => null, 'quote' => 'Great.']);

        $response = $this->get('/testimonials')->assertOk();

        // Monogram badge with the initial, no broken image element for this person.
        $response->assertSee('Zoltan Q.');
        $response->assertSee('bg-secondary font-display', false);
    }
}
