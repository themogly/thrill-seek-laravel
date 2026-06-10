<?php

namespace Tests\Feature;

use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\TestimonialSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class TestimonialsTest extends TestCase
{
    public function test_testimonials_page_lists_all_testimonials_in_order(): void
    {
        Testimonial::factory()->create(['name' => 'Second Z.', 'quote' => 'Second quote here.', 'sort_order' => 2]);
        Testimonial::factory()->create(['name' => 'First A.', 'quote' => 'First quote here.', 'sort_order' => 1]);

        $response = $this->get('/testimonials');

        $response->assertOk();
        $response->assertSeeInOrder(['First A.', 'Second Z.']);
    }

    public function test_home_page_shows_first_three_featured_testimonials_using_excerpts(): void
    {
        Testimonial::factory()->featured()->create([
            'name' => 'Featured F.',
            'quote' => 'The long version of this quote.',
            'excerpt' => 'The short version.',
            'sort_order' => 1,
        ]);
        Testimonial::factory()->create(['name' => 'Hidden H.', 'sort_order' => 2]);
        Testimonial::factory()->featured()->count(3)->create(['sort_order' => 3]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('The short version.');
        $response->assertDontSee('The long version of this quote.');
        $response->assertDontSee('Hidden H.');
    }

    public function test_seeded_content_matches_the_original_static_pages(): void
    {
        $this->seed(TestimonialSeeder::class);

        $this->get('/testimonials')
            ->assertSee("Absolutely life-changing. The team made me feel safe from the moment I arrived. I'll be back!")
            ->assertSee('Lucy is an incredible coach. Clear, patient, and genuinely cares about your progress.');

        $this->get('/')
            ->assertSee('Absolutely life-changing. The team made me feel safe from the moment I arrived.')
            ->assertSee('Did my AFF with G-Force in Spain. Best decision I ever made — incredible coaches.')
            ->assertSee('Tandem from 15,000ft. The view, the rush, the team. 10/10.');
    }

    public function test_content_cache_is_busted_when_a_testimonial_is_saved(): void
    {
        $testimonial = Testimonial::factory()->create(['quote' => 'Original quote.']);
        $this->get('/testimonials')->assertSee('Original quote.');

        $testimonial->update(['quote' => 'Updated quote.']);

        $this->get('/testimonials')->assertSee('Updated quote.')->assertDontSee('Original quote.');
    }

    public function test_admin_can_create_a_testimonial(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateTestimonial::class)
            ->fillForm([
                'name' => 'New N.',
                'role' => 'Tandem jumper',
                'quote' => 'Created through the admin panel.',
                'featured' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('testimonials', ['name' => 'New N.', 'featured' => true]);
    }
}
