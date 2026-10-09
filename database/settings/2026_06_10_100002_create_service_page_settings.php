<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tandem_page.seo_title', 'Tandem Skydive from 15,000ft — G-Force Skydiving');
        $this->migrator->add('tandem_page.seo_description', 'Book a G-Force Buzz Tandem Skydive from {price:tandem-skydive}. The highest tandem in the UK at 15,000ft. Devon, Swansea and Hinton.');
        $this->migrator->add('tandem_page.og_title', 'Tandem Skydive — G-Force');
        $this->migrator->add('tandem_page.og_description', 'Highest tandem in the UK at 15,000ft.');
        $this->migrator->add('tandem_page.hero_title', 'Tandem Skydive');
        $this->migrator->add('tandem_page.hero_subtitle', 'G-Force Buzz Tandems from {price:tandem-skydive} — the highest tandem in the UK at 15,000ft.');
        $this->migrator->add('tandem_page.intro_eyebrow', 'The jump');
        $this->migrator->add('tandem_page.intro_title', '15,000ft of pure adrenaline');
        $this->migrator->add('tandem_page.intro_lead', "Strapped to a fully qualified G-Force instructor, you'll experience nearly a full minute of freefall and the most breathtaking views in the UK.");
        $this->migrator->add('tandem_page.bullets', [
            'Highest tandem skydive in the UK',
            'Approx 60 seconds of freefall',
            '5–7 minutes of canopy flight',
            'Fully briefed by your instructor on the day',
            'G-Force Buzz tandem rigs',
        ]);
        $this->migrator->add('tandem_page.locations_heading', 'Locations');
        $this->migrator->add('tandem_page.locations', ['Devon', 'Swansea', 'Hinton Midlands']);
        $this->migrator->add('tandem_page.intro_image', '/images/tandem.webp');
        $this->migrator->add('tandem_page.pricing_eyebrow', 'Transparent pricing');
        $this->migrator->add('tandem_page.pricing_title', 'What it costs');
        $this->migrator->add('tandem_page.charity_note_title', 'Charity tandem?');
        $this->migrator->add('tandem_page.charity_note_body', 'Your sponsorship can cover the {price:tandem-skydive} jump fee — camera packages must still be paid separately.');

        $this->migrator->add('aff_page.seo_title', 'AFF Course — Become a Licensed Skydiver | G-Force');
        $this->migrator->add('aff_page.seo_description', 'Accelerated Freefall course Levels 1–8 for {price:aff-course}. British Skydiving / USPA recognised. Train in Spain with G-Force.');
        $this->migrator->add('aff_page.hero_title', 'Accelerated Freefall');
        $this->migrator->add('aff_page.hero_subtitle', 'Beginner to A Licence — fully qualified to skydive solo, anywhere in the world.');
        $this->migrator->add('aff_page.intro_eyebrow', 'The course');
        $this->migrator->add('aff_page.intro_title', 'From your first jump to a Licence');
        $this->migrator->add('aff_page.intro_lead', 'A British Skydiving / USPA recognised programme delivered by ex-military instructors.');
        $this->migrator->add('aff_page.bullets', [
            'Full UK ground school: equipment, safety, emergency drills, canopy control',
            'Levels 1–8 freefall progression with two instructors',
            '10 consolidation jumps to A Licence',
            'All equipment and instruction included',
        ]);
        $this->migrator->add('aff_page.intro_image', '/images/aff.webp');
        $this->migrator->add('aff_page.trust_eyebrow', 'Train with confidence');
        $this->migrator->add('aff_page.trust_title', "You're in safe hands");
        $this->migrator->add('aff_page.trust_body', "Our AFF programme is delivered by ex-military instructors with decades of experience and the highest recognised certifications. Before you book, here's what stands behind every jump.");
        $this->migrator->add('aff_page.pricing_eyebrow', 'Pricing');
        $this->migrator->add('aff_page.pricing_title', 'Investment');
        $this->migrator->add('aff_page.info_eyebrow', 'Where & When');
        $this->migrator->add('aff_page.info_title', 'Train in the sun');
        $this->migrator->add('aff_page.info_lead', 'Our courses run in Spain — guaranteed jumpable weather, an unforgettable trip.');
        $this->migrator->add('aff_page.info_cards', [
            ['icon' => 'map-pin', 'title' => 'Location', 'body' => 'Spain — sunny, reliable weather, world-class dropzone.'],
            ['icon' => 'plane', 'title' => 'Travel', 'body' => 'Flights from Birmingham or Bristol, group accommodation arranged, shared car hire.'],
            ['icon' => 'graduation-cap', 'title' => 'Example dates', 'body' => '8–12 June — limited group sizes for personalised coaching.'],
        ]);

        $this->migrator->add('coached_page.seo_title', 'Coached Advanced Flying Skills — G-Force Skydiving');
        $this->migrator->add('coached_page.seo_description', '1-to-1 advanced skydiving coaching from {price:coached-skills}. Belly, freefly, tracking and canopy skills.');
        $this->migrator->add('coached_page.hero_title', 'Coached Skills');
        $this->migrator->add('coached_page.hero_subtitle', '1-to-1 advanced flying coaching to take your skydiving to the next level.');
        $this->migrator->add('coached_page.price_eyebrow', 'From {price:coached-skills} per session');
        $this->migrator->add('coached_page.heading', 'Fly Better. Fly Smarter.');
        $this->migrator->add('coached_page.body', "Whether you're chasing your B licence, working on freefly, tracking or canopy control, our coaches give you focused 1-to-1 attention with video debrief and a personalised plan.");
        $this->migrator->add('coached_page.skills', ['Belly flying & RW', 'Freefly progression', 'Tracking & angle flying', 'Canopy piloting', 'Video debrief included']);
        $this->migrator->add('coached_page.image', '/images/coached.webp');
        $this->migrator->add('coached_page.button_label', 'Book a session');
    }
};
