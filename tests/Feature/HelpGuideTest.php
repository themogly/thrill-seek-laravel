<?php

namespace Tests\Feature;

use App\Filament\Pages\HelpGuide;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class HelpGuideTest extends TestCase
{
    public function test_admins_can_read_the_in_panel_guide(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(HelpGuide::class)
            ->assertOk()
            ->assertSee('How it all works')
            ->assertSee('Prices are in pounds')
            ->assertSee('at least 5 days', false);
    }

    public function test_the_guide_requires_login(): void
    {
        $this->get('/admin/help-guide')->assertRedirect();
    }

    public function test_every_open_screen_link_resolves(): void
    {
        $this->actingAs(User::factory()->create());

        $links = collect((new HelpGuide)->sections())
            ->pluck('cta')
            ->filter()
            ->pluck('url');

        $this->assertGreaterThan(5, $links->count());

        foreach ($links as $url) {
            // Each "Open …" link points at a real admin screen, never a 404.
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_the_guide_covers_every_major_area(): void
    {
        $titles = collect((new HelpGuide)->sections())->pluck('title')->implode(' | ');

        foreach (['Products', 'Tandem dates', 'AFF courses', 'Locations', 'Bookings', 'Enquiries',
            'Taking payment', 'vouchers', 'Newsletter', 'News articles', 'Course communications',
            'Feature toggles', 'Email templates', 'launch checklist'] as $needle) {
            $this->assertStringContainsStringIgnoringCase($needle, $titles, "Help guide is missing a topic for: {$needle}");
        }
    }
}
