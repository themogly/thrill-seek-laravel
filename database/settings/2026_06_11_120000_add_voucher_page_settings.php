<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('simple_pages.voucher_seo_title', 'Gift Vouchers — G-Force Skydiving');
        $this->migrator->add('simple_pages.voucher_seo_description', 'Give a tandem skydive from 15,000ft. Buy a gift voucher online — delivered by email, valid 12 months.');
        $this->migrator->add('simple_pages.voucher_hero_title', 'Give the Jump');
        $this->migrator->add('simple_pages.voucher_hero_subtitle', 'A tandem skydive from 15,000ft — the gift nobody forgets. Bought online, delivered by email in minutes.');
        $this->migrator->add('simple_pages.voucher_intro', 'Vouchers are valid for 12 months, transferable, and redeemable straight from our online booking — the lucky recipient just enters the code.');
    }
};
