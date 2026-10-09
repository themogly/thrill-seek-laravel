<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('simple_pages.shop_seo_title', 'Shop — G-Force Skydiving Merch');
        $this->migrator->add('simple_pages.shop_seo_description', 'G-Force Skydiving merch: t-shirts, tech tops, jumpsuits, buffs and more.');
        $this->migrator->add('simple_pages.shop_hero_title', 'Shop');
        $this->migrator->add('simple_pages.shop_hero_subtitle', 'Kit up. Look the part. Repping G-Force on the dropzone.');

        $this->migrator->add('simple_pages.testimonials_seo_title', 'Testimonials — G-Force Skydiving');
        $this->migrator->add('simple_pages.testimonials_seo_description', 'Real reviews from G-Force tandem students and AFF graduates.');
        $this->migrator->add('simple_pages.testimonials_hero_title', 'Testimonials');
        $this->migrator->add('simple_pages.testimonials_hero_subtitle', "Real stories from the people who've jumped with us.");

        $this->migrator->add('simple_pages.hall_of_fame_seo_title', 'Hall of Fame — G-Force Skydiving');
        $this->migrator->add('simple_pages.hall_of_fame_seo_description', 'Celebrating our students and graduates — the G-Force Hall of Fame.');
        $this->migrator->add('simple_pages.hall_of_fame_hero_title', 'Hall of Fame');
        $this->migrator->add('simple_pages.hall_of_fame_hero_subtitle', 'The students, graduates and coaches that make G-Force what it is.');

        $this->migrator->add('simple_pages.contact_seo_title', 'Contact G-Force Skydiving');
        $this->migrator->add('simple_pages.contact_seo_description', 'Get in touch with G-Force Skydiving. Call +44 (0)7583 155 951 or email info@gforceskydiving.co.uk.');
        $this->migrator->add('simple_pages.contact_hero_title', 'Contact Us');
        $this->migrator->add('simple_pages.contact_hero_subtitle', "Questions? Bookings? Charity jumps? Get in touch and we'll come back to you fast.");
        $this->migrator->add('simple_pages.contact_form_heading', 'Send a message');
        $this->migrator->add('simple_pages.contact_direct_heading', 'Direct contact');
        $this->migrator->add('simple_pages.contact_newsletter_heading', 'Newsletter');
        $this->migrator->add('simple_pages.contact_newsletter_text', 'Course dates and offers, no spam.');

        $this->migrator->add('simple_pages.privacy_title', 'Privacy Policy');
        $this->migrator->add('simple_pages.privacy_body', '<p>G-Force Skydiving respects your privacy. We collect only the information needed to book and run your skydiving experience: name, contact details, date of birth, height and weight for safety, and any medical information you choose to share.</p><p class="mt-4">We never sell your data. We store it securely and only share it with British Skydiving / dropzone partners when required for your jump. You may request deletion of your data at any time by emailing info@gforceskydiving.co.uk.</p><p class="mt-4">For any questions about how we handle your data, contact us at info@gforceskydiving.co.uk.</p>');

        $this->migrator->add('simple_pages.terms_title', 'Terms & Conditions');
        $this->migrator->add('simple_pages.terms_body', '<p>By booking with G-Force Skydiving you agree to the following terms.</p><p><strong>Payments:</strong> Tandem skydive fees are paid direct to G-Force. P6 Third Party Insurance ({addon:p6-third-party-insurance}) is paid on the day. Camera packages are paid separately from any charity sponsorship.</p><p><strong>Rebooking:</strong> A {addon:rebooking-fee} rebooking fee applies when you need to reschedule. Weather cancellations are not charged.</p><p><strong>Safety:</strong> All jumpers must meet our weight and medical criteria. Jumpers over 18 stone require an assessment.</p><p><strong>Refunds:</strong> Deposits are non-refundable but transferable subject to availability.</p><p>For questions, contact info@gforceskydiving.co.uk.</p>');
    }
};
