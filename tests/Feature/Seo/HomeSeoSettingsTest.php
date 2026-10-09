<?php

namespace Tests\Feature\Seo;

use App\Filament\Pages\Settings\ManageHomePageSettings;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\HomePageSettings;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The homepage <title>/description come from Home's own SEO settings (013),
 * seeded with the literals the template hardcoded so nothing changes on deploy.
 */
class HomeSeoSettingsTest extends TestCase
{
    private const TITLE = 'G-Force Skydiving — One Life. One Adventure. Live It.';

    private const DESCRIPTION = 'UK-based skydiving school offering tandem jumps, AFF courses and advanced coaching. Book your jump today.';

    public function test_fresh_settings_render_todays_title_and_description(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>'.e(self::TITLE).'</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="'.e(self::DESCRIPTION).'" />', $html);
        $this->assertStringContainsString('<meta property="og:title" content="'.e(self::TITLE).'" />', $html);
    }

    public function test_changing_the_setting_changes_the_homepage_head(): void
    {
        $home = app(HomePageSettings::class);
        $home->seo_title = 'Tandem Skydives & AFF Courses | G-Force';
        $home->seo_description = 'Jump with the UK’s friendliest instructors — tandem & AFF.';
        $home->save();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Tandem Skydives &amp; AFF Courses | G-Force</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Jump with the UK’s friendliest instructors — tandem &amp; AFF." />', $html);
        $this->assertStringNotContainsString(e(self::TITLE), $html);
    }

    public function test_the_general_default_title_does_not_override_the_homepage(): void
    {
        // Decision (b): Home has its own fields; the site-wide default is for pages that set none.
        $general = app(GeneralSettings::class);
        $general->seo_title = 'Some site-wide default';
        $general->save();

        $this->get('/')->assertOk()->assertSee('<title>'.e(self::TITLE).'</title>', false);
    }

    public function test_an_empty_or_missing_value_falls_back_to_todays_literals(): void
    {
        $home = app(HomePageSettings::class);
        $home->seo_title = '';
        $this->assertSame(self::TITLE, $home->seoTitle());

        unset($home->seo_description); // as from a settings cache that predates the property
        $this->assertSame(self::DESCRIPTION, $home->seoDescription());
    }

    public function test_the_owner_edits_it_on_the_home_settings_screen(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageHomePageSettings::class)
            ->assertFormSet(['seo_title' => self::TITLE, 'seo_description' => self::DESCRIPTION])
            ->fillForm(['seo_title' => 'New home title', 'seo_description' => 'New home description.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New home title', app(HomePageSettings::class)->seoTitle());
        $this->get('/')->assertSee('<title>New home title</title>', false);
    }
}
