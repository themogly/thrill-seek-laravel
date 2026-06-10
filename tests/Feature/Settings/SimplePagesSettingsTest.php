<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ManageSimplePagesSettings;
use App\Models\User;
use App\Settings\SimplePagesSettings;
use Livewire\Livewire;
use Tests\TestCase;

class SimplePagesSettingsTest extends TestCase
{
    public function test_simple_pages_render_the_seeded_settings_content(): void
    {
        $this->get('/shop')->assertOk()->assertSee('Repping G-Force on the dropzone.');
        $this->get('/testimonials')->assertOk()->assertSee('Real stories from the people who&#039;ve jumped with us.', false);
        $this->get('/hall-of-fame')->assertOk()->assertSee('The students, graduates and coaches that make G-Force what it is.');
        $this->get('/contact')->assertOk()->assertSee('Send a message')->assertSee('Direct contact');
        $this->get('/privacy')->assertOk()->assertSee('G-Force Skydiving respects your privacy.', false);
        $this->get('/terms')->assertOk()->assertSee('A £50 rebooking fee applies when you need to reschedule.', false);
    }

    public function test_updated_settings_change_the_legal_pages(): void
    {
        $settings = app(SimplePagesSettings::class);
        $settings->privacy_body = '<p>Completely rewritten privacy copy.</p>';
        $settings->terms_body = '<p>Completely rewritten terms copy.</p>';
        $settings->save();

        $this->get('/privacy')->assertSee('Completely rewritten privacy copy.');
        $this->get('/terms')->assertSee('Completely rewritten terms copy.');
    }

    public function test_admin_can_update_simple_pages_through_the_filament_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSimplePagesSettings::class)
            ->assertOk()
            ->fillForm(['shop_hero_subtitle' => 'New shop subtitle.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New shop subtitle.', app(SimplePagesSettings::class)->refresh()->shop_hero_subtitle);
    }
}
