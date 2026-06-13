<?php

namespace Tests;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Flip a public feature toggle (e.g. 'shop_enabled') for the test. */
    protected function setFeature(string $feature, bool $enabled): void
    {
        $settings = app(GeneralSettings::class);
        $settings->{$feature} = $enabled;
        $settings->save();
    }
}
