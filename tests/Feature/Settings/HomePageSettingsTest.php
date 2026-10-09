<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ManageHomePageSettings;
use App\Models\GalleryImage;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\HomePageSettings;
use App\Settings\SimplePagesSettings;
use Database\Seeders\GalleryImageSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageSettingsTest extends TestCase
{
    public function test_settings_resolve_after_removing_orphaned_keys(): void
    {
        // Removing the orphaned keys + properties must not break settings access
        // (Spatie throws MissingSettings if a declared property has no stored key).
        $home = app(HomePageSettings::class);
        $this->assertIsString($home->instagram_caption);

        // team_lead joined the removed list in 012 (orphaned since b494dd6).
        foreach (['about_stats', 'team_eyebrow', 'team_title', 'instagram_note', 'team_lead'] as $removed) {
            $this->assertFalse(property_exists($home, $removed), "HomePageSettings::{$removed} should be gone");
        }

        $simple = app(SimplePagesSettings::class);
        $this->assertIsString($simple->meet_the_team_hero_title);
        $this->assertFalse(property_exists($simple, 'home_team_teaser_line'));
    }

    public function test_home_page_renders_the_seeded_settings_content(): void
    {
        $this->seed(GalleryImageSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('One Life.');
        $response->assertSee('One Adventure.');
        $response->assertSee('Established 2017. Built on experience.');
        // about_stats tiles were removed from the About section (they duplicated the
        // Trust band); the credentials now appear once, in the band below.
        $response->assertDontSee('Highest UK Tandem');
        $response->assertSee('Follow @gforceskydiving for jumps from the weekend.');
        $response->assertSee('Trusted. Certified. Experienced.');
        $response->assertSee('Ex-Military');
        $response->assertSee('Ready to Jump?');
        $response->assertSee('/images/hero-skydive.webp');
    }

    public function test_updated_settings_change_the_home_page(): void
    {
        $settings = app(HomePageSettings::class);
        $settings->hero_subtitle = 'A brand new subtitle for the hero.';
        $settings->cta_title = 'Jump Tomorrow?';
        $settings->save();

        $this->get('/')
            ->assertSee('A brand new subtitle for the hero.')
            ->assertSee('Jump Tomorrow?');
    }

    public function test_gallery_images_render_in_order(): void
    {
        GalleryImage::create(['image' => '/images/second.jpg', 'sort_order' => 2]);
        GalleryImage::create(['image' => '/images/first.jpg', 'sort_order' => 1]);

        $this->get('/')->assertSeeInOrder(['/images/first.jpg', '/images/second.jpg']);
    }

    public function test_admin_can_update_home_settings_through_the_filament_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageHomePageSettings::class)
            ->assertOk()
            ->fillForm(['hero_title_highlight' => 'One Sky.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('One Sky.', app(HomePageSettings::class)->refresh()->hero_title_highlight);
    }

    public function test_trust_items_are_editable_via_general_settings(): void
    {
        $settings = app(GeneralSettings::class);
        $items = $settings->trust_items;
        $items[0]['value'] = 'Battle-Tested';
        $settings->trust_items = $items;
        $settings->save();

        $this->get('/')->assertSee('Battle-Tested');
    }
}
