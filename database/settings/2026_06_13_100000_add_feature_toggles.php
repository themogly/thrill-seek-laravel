<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Shop is off by default — the storefront has no checkout yet (Round 7).
        $this->migrator->add('general.shop_enabled', false);
        // Online payments on by default — the site keeps its public Stripe checkout.
        $this->migrator->add('general.online_payments_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.online_payments_enabled');
        $this->migrator->delete('general.shop_enabled');
    }
};
