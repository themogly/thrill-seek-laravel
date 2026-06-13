<?php

namespace Tests\Feature;

use App\Settings\AffPageSettings;
use App\Settings\HomePageSettings;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class OptionalLeadTextTest extends TestCase
{
    public function test_section_heading_omits_the_lead_block_when_blank(): void
    {
        $withLead = Blade::render('<x-site.section-heading title="Title" lead="Some lead" />');
        $this->assertStringContainsString('Some lead', $withLead);
        $this->assertStringContainsString('mt-5', $withLead);

        // Blank lead → no paragraph and none of its top-margin spacing left behind.
        $blank = Blade::render('<x-site.section-heading title="Title" :lead="null" />');
        $this->assertStringNotContainsString('mt-5', $blank);
    }

    public function test_home_subtitles_disappear_when_settings_are_blank(): void
    {
        $this->seed(ProductSeeder::class);

        $settings = app(HomePageSettings::class);
        $settings->newsletter_subtitle = '';
        $settings->cta_subtitle = '';
        $settings->save();

        $this->get('/')
            ->assertOk()
            // The headings remain; the now-empty leads are gone, gap and all.
            ->assertSee($settings->newsletter_title)
            ->assertDontSee('mt-4 text-lg text-white/85', false);
    }

    public function test_service_page_lead_is_optional_in_the_admin_form(): void
    {
        // info_lead is no longer required, so saving a blank value is accepted.
        $settings = app(AffPageSettings::class);
        $settings->info_lead = '';
        $settings->save();

        $this->seed(ProductSeeder::class);
        $this->get('/aff')->assertOk();
    }
}
