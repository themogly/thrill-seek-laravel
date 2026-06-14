<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Drives the "Last updated" line; auto-bumped whenever the body is saved
        // (see ManageSimplePagesSettings::save). Seed = the date of this upgrade.
        $this->migrator->add('simple_pages.privacy_updated_at', '2026-06-14');

        // Upgrade to the stronger UK-GDPR-aware default — but ONLY if the owner
        // hasn't already edited the original seeded copy. A conditional update is
        // the "don't clobber owner edits on re-seed" pattern for spatie settings.
        // The [Owner: …] lines are editable placeholders; the {{ }} tokens resolve
        // live at render (App\ViewModels\PrivacyPage), never hardcoded here.
        $old = $this->originalDefault();
        $new = $this->improvedDefault();

        $this->migrator->update(
            'simple_pages.privacy_body',
            fn (string $current): string => $current === $old ? $new : $current,
        );
    }

    private function originalDefault(): string
    {
        return '<p>G-Force Skydiving respects your privacy. We collect only the information needed to book and run your skydiving experience: name, contact details, date of birth, height and weight for safety, and any medical information you choose to share.</p><p class="mt-4">We never sell your data. We store it securely and only share it with British Skydiving / dropzone partners when required for your jump. You may request deletion of your data at any time by emailing info@gforceskydiving.co.uk.</p><p class="mt-4">For any questions about how we handle your data, contact us at info@gforceskydiving.co.uk.</p>';
    }

    private function improvedDefault(): string
    {
        return <<<'HTML'
<p class="text-sm text-muted-foreground">Last updated: {{ last_updated }}</p>
<p class="mt-4">{{ business_name }} ("we") respects your privacy and is committed to protecting your personal data. This policy explains what we collect, why, how we look after it, and your rights. We are the data controller for the information you give us.</p>
<p class="mt-4"><strong>What we collect.</strong> To book and safely run your skydiving experience we collect: your name and contact details; your date of birth, height and weight (needed for safety and equipment); and any medical information you choose to share. Health and medical information is "special category" data under UK GDPR, and we only process it with your explicit consent, solely to keep you safe on the day.</p>
<p class="mt-4"><strong>Why we collect it (our legal basis).</strong> We process your booking and contact details to perform our contract with you (your booking) and for our legitimate interest in running the business. We process medical and health information only on the basis of your explicit consent, for safety. Where you opt in to our newsletter, we send marketing on the basis of your consent, which you can withdraw at any time using the unsubscribe link in any email.</p>
<p class="mt-4"><strong>Who we share it with.</strong> We never sell your data. We share it only where necessary to deliver your jump — for example with British Skydiving and our dropzone/instructor partners — and with our payment provider (Stripe) to take payment and our email provider to contact you. We do not share your data for anyone else's marketing.</p>
<p class="mt-4"><strong>If you're under 18.</strong> Where we accept bookings for under-18s, we require consent from a parent or guardian, and we only collect the information needed for that booking. [Owner: set out your actual minimum age and guardian-consent process here.]</p>
<p class="mt-4"><strong>How long we keep it.</strong> We keep your booking and safety records only as long as needed for the jump and for our legal, insurance and accounting obligations, after which we securely delete or anonymise them. [Owner: state your retention period, e.g. booking records for X years for insurance/accounting.]</p>
<p class="mt-4"><strong>How we protect it.</strong> We store your data securely, limit access to those who need it, and hold medical information separately from general contact details where possible.</p>
<p class="mt-4"><strong>Your rights.</strong> Under UK GDPR you have the right to access a copy of your data, to have it corrected, to have it deleted, to restrict or object to how we use it, and to data portability. To exercise any of these, email {{ contact_email }}. You also have the right to complain to the Information Commissioner's Office (ICO) at ico.org.uk if you're unhappy with how we've handled your data.</p>
<p class="mt-4"><strong>Contact.</strong> For any privacy question, contact us at {{ contact_email }}.</p>
HTML;
    }
};
