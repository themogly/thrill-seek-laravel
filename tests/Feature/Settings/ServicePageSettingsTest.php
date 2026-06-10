<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ManageAffPageSettings;
use App\Filament\Pages\Settings\ManageCoachedPageSettings;
use App\Filament\Pages\Settings\ManageTandemPageSettings;
use App\Models\User;
use App\Settings\AffPageSettings;
use App\Settings\CoachedPageSettings;
use App\Settings\TandemPageSettings;
use Database\Seeders\ProductSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class ServicePageSettingsTest extends TestCase
{
    public function test_tandem_page_renders_the_seeded_settings_content(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/tandem')
            ->assertOk()
            ->assertSee('Tandem Skydive from 15,000ft — G-Force Skydiving')
            ->assertSee('15,000ft of pure adrenaline')
            ->assertSee('Highest tandem skydive in the UK')
            ->assertSee('Hinton Midlands')
            ->assertSee('Charity tandem?');
    }

    public function test_aff_page_renders_the_seeded_settings_content(): void
    {
        $this->get('/aff')
            ->assertOk()
            ->assertSee('Accelerated Freefall')
            ->assertSee('From your first jump to a Licence')
            ->assertSee('10 consolidation jumps to A Licence')
            ->assertSee('You&#039;re in safe hands', false)
            ->assertSee('Train in the sun')
            ->assertSee('Spain — sunny, reliable weather, world-class dropzone.');
    }

    public function test_coached_page_renders_the_seeded_settings_content(): void
    {
        $this->get('/coached')
            ->assertOk()
            ->assertSee('From £60 per session')
            ->assertSee('Fly Better. Fly Smarter.')
            ->assertSee('Freefly progression')
            ->assertSee('Book a session');
    }

    public function test_updated_service_page_settings_change_the_pages(): void
    {
        $tandem = app(TandemPageSettings::class);
        $tandem->hero_title = 'Tandem Madness';
        $tandem->save();

        $aff = app(AffPageSettings::class);
        $aff->intro_title = 'Zero to Hero';
        $aff->save();

        $coached = app(CoachedPageSettings::class);
        $coached->heading = 'Fly Like a Pro.';
        $coached->save();

        // The page-hero component wraps each title word in its own <span>,
        // so assert the distinctive word rather than the full phrase.
        $this->get('/tandem')->assertSee('Madness');
        $this->get('/aff')->assertSee('Zero to Hero');
        $this->get('/coached')->assertSee('Fly Like a Pro.');
    }

    public function test_admin_can_update_each_service_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageTandemPageSettings::class)
            ->fillForm(['hero_title' => 'New Tandem Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(ManageAffPageSettings::class)
            ->fillForm(['hero_title' => 'New AFF Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(ManageCoachedPageSettings::class)
            ->fillForm(['hero_title' => 'New Coached Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Tandem Title', app(TandemPageSettings::class)->refresh()->hero_title);
        $this->assertSame('New AFF Title', app(AffPageSettings::class)->refresh()->hero_title);
        $this->assertSame('New Coached Title', app(CoachedPageSettings::class)->refresh()->hero_title);
    }
}
