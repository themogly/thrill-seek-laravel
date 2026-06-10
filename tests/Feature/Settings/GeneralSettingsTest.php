<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ManageGeneralSettings;
use App\Models\User;
use App\Settings\GeneralSettings;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    public function test_layout_and_footer_render_general_settings(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('+44 (0)7583 155 951');
        $response->assertSee('info@gforceskydiving.co.uk');
        $response->assertSee('©2026 G-Force Skydiving. All rights reserved.');
    }

    public function test_updated_settings_change_the_rendered_site(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->phone = '+44 (0)1234 567 890';
        $settings->footer_copyright = '©2030 New Owner Ltd.';
        $settings->save();

        $response = $this->get('/contact');

        $response->assertSee('+44 (0)1234 567 890');
        $response->assertSee('©2030 New Owner Ltd.');
        $response->assertDontSee('©2026 G-Force Skydiving. All rights reserved.');
    }

    public function test_phone_href_strips_formatting(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->phone = '+44 (0)7583 155 951';

        $this->assertSame('tel:+4407583155951', $settings->phoneHref());
    }

    public function test_admin_can_update_settings_through_the_filament_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageGeneralSettings::class)
            ->assertOk()
            ->fillForm(['site_name' => 'Renamed School'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed School', app(GeneralSettings::class)->refresh()->site_name);
    }

    public function test_guests_cannot_view_the_settings_page(): void
    {
        $this->get('/admin/settings/general')->assertRedirect();
    }
}
