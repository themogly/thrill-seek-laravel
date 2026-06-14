<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use App\Settings\SimplePagesSettings;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    public function test_it_renders_the_improved_uk_gdpr_policy(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('special category', escape: false)
            ->assertSee('UK GDPR', escape: false)
            ->assertSee('Information Commissioner', escape: false)
            // The [Owner: …] placeholders stay visible for the owner to complete.
            ->assertSee('[Owner:', escape: false);
    }

    public function test_business_name_contact_email_and_date_resolve_live_from_settings(): void
    {
        app(GeneralSettings::class)->fill([
            'site_name' => 'G-Force Test Co',
            'email' => 'privacy-sentinel@gforce.test',
        ])->save();
        app(SimplePagesSettings::class)->fill(['privacy_updated_at' => '2026-06-14'])->save();

        $html = $this->get('/privacy')->assertOk()->getContent();

        // Variables resolve at render — no literal tokens leak.
        $this->assertStringContainsString('G-Force Test Co', $html);
        $this->assertStringContainsString('mailto:privacy-sentinel@gforce.test', $html);
        $this->assertStringContainsString('14 June 2026', $html);
        $this->assertStringNotContainsString('{{ business_name }}', $html);
        $this->assertStringNotContainsString('{{ contact_email }}', $html);
        $this->assertStringNotContainsString('{{ last_updated }}', $html);
    }

    public function test_editing_the_contact_email_setting_updates_the_policy(): void
    {
        app(GeneralSettings::class)->fill(['email' => 'first@gforce.test'])->save();
        $this->get('/privacy')->assertSee('first@gforce.test', escape: false);

        app(GeneralSettings::class)->fill(['email' => 'changed@gforce.test'])->save();
        $this->get('/privacy')
            ->assertSee('changed@gforce.test', escape: false)
            ->assertDontSee('first@gforce.test', escape: false);
    }
}
