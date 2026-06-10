<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('coached_page.enquiry_eyebrow', 'Get coached');
        $this->migrator->add('coached_page.enquiry_title', 'Tell us where you’re at');
        $this->migrator->add('coached_page.enquiry_lead', 'Every coaching plan is personal. Tell us your discipline and experience and we’ll come back with a plan and a price.');

        $this->migrator->add('tandem_page.gift_title', 'Give the jump of a lifetime');
        $this->migrator->add('tandem_page.gift_body', 'Gift vouchers for tandem skydives are perfect for birthdays and big occasions — valid for 12 months and transferable. Get in touch and we’ll sort one out the same day.');
        $this->migrator->add('tandem_page.gift_button_label', 'Ask about gift vouchers');
    }
};
