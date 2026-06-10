<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ManageHomePageSettings;
use App\Models\GalleryImage;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\HomePageSettings;
use Database\Seeders\GalleryImageSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageSettingsTest extends TestCase
{
    public function test_home_page_renders_the_seeded_settings_content(): void
    {
        $this->seed(GalleryImageSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('One Life.');
        $response->assertSee('One Adventure.');
        $response->assertSee('Established 2017. Built on experience.');
        $response->assertSee('Highest UK Tandem');
        $response->assertSee('Follow @gforceskydiving for jumps from the weekend.');
        $response->assertSee('AFF Course in Spain — June 8–12');
        $response->assertSee('Trusted. Certified. Experienced.');
        $response->assertSee('Ex-Military');
        $response->assertSee('Ready to Jump?');
        $response->assertSee('/images/hero-skydive.jpg');
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
