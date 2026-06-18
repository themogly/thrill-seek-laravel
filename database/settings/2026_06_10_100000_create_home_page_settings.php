<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('home.hero_eyebrow', 'G-Force Skydiving');
        $this->migrator->add('home.hero_title_1', 'One Life.');
        $this->migrator->add('home.hero_title_highlight', 'One Adventure.');
        $this->migrator->add('home.hero_title_2', 'Live It.');
        $this->migrator->add('home.hero_subtitle', "Jump with the UK's most experienced skydiving coaches. Military trained. BS & USPA certified.");
        $this->migrator->add('home.hero_image', '/images/hero-skydive.webp');
        $this->migrator->add('home.hero_cta_primary_label', 'Book a Tandem');
        $this->migrator->add('home.hero_cta_secondary_label', 'Learn to Skydive');

        $this->migrator->add('home.services_eyebrow', 'What we do');
        $this->migrator->add('home.services_title', 'Three Ways to Fly');
        $this->migrator->add('home.services_lead', 'From a once-in-a-lifetime tandem to a full skydiving licence.');

        $this->migrator->add('home.about_eyebrow', 'Our Story');
        $this->migrator->add('home.about_title', 'Established 2017. Built on experience.');
        $this->migrator->add('home.about_body', 'G-Force Skydiving was founded by ex-military jumpers with a passion for sharing the sport safely. With over 30 years of combined experience, our team holds both British Skydiving and USPA certifications, and operates across the UK and Europe.');
        $this->migrator->add('home.about_stats', [
            ['icon' => 'plane', 'value' => '15k ft', 'label' => 'Highest UK Tandem'],
            ['icon' => 'users', 'value' => '30+ yrs', 'label' => 'Combined Experience'],
            ['icon' => 'award', 'value' => 'BS / USPA', 'label' => 'Certified'],
        ]);
        $this->migrator->add('home.about_image_1', '/images/tandem.webp');
        $this->migrator->add('home.about_image_2', '/images/aff.webp');

        $this->migrator->add('home.trust_eyebrow', 'Why jump with us');
        $this->migrator->add('home.trust_title', 'Trusted. Certified. Experienced.');

        $this->migrator->add('home.team_eyebrow', 'Meet the team');
        $this->migrator->add('home.team_title', 'The Coaches');
        $this->migrator->add('home.team_lead', "The people you'll fly with.");

        $this->migrator->add('home.instagram_caption', 'Follow @gforceskydiving for jumps from the weekend.');
        $this->migrator->add('home.instagram_note', 'Live Instagram feed connects via Meta Graph API — ask to enable.');
        $this->migrator->add('home.facebook_caption', 'See our latest news and jump days.');
        $this->migrator->add('home.facebook_posts', [
            ['title' => 'AFF Course in Spain — June 8–12', 'description' => 'Limited spots left. Sun, blue skies and 8 jumps to A-licence.'],
            ['title' => 'Charity Tandem Day at Devon', 'description' => 'Raise money for your cause and jump from 15,000ft.'],
            ['title' => 'New G-Force Buzz tandems available', 'description' => 'Upgrade your booking with our latest kit.'],
        ]);

        $this->migrator->add('home.testimonials_eyebrow', 'Real reviews');
        $this->migrator->add('home.testimonials_title', 'Voices from the Sky');

        $this->migrator->add('home.newsletter_title', 'Stay in the loop');
        $this->migrator->add('home.newsletter_subtitle', 'Course dates, jump days and member offers — direct to your inbox.');

        $this->migrator->add('home.cta_title', 'Ready to Jump?');
        $this->migrator->add('home.cta_subtitle', "Get in touch — we'll answer any question and help you pick the right experience.");
        $this->migrator->add('home.cta_button_label', 'Contact Us');
    }
};
