<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use Tests\TestCase;

class EmailSignoffTest extends TestCase
{
    public function test_returns_the_configured_signoff(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->email_signoff = 'Cheers, The Team';

        $this->assertSame('Cheers, The Team', $settings->emailSignoff());
    }

    public function test_falls_back_to_default_when_the_property_is_uninitialized(): void
    {
        // Simulate a stale settings cache that predates the email_signoff property:
        // the typed property is never initialised, so a raw read would throw and fail
        // every queued email. emailSignoff() must degrade to the default instead.
        $settings = app(GeneralSettings::class);
        unset($settings->email_signoff);

        $this->assertSame("Blue skies,\nThe G-Force team", $settings->emailSignoff());
    }

    public function test_falls_back_to_default_when_blank(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->email_signoff = '';

        $this->assertSame("Blue skies,\nThe G-Force team", $settings->emailSignoff());
    }
}
