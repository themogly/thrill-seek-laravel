<?php

namespace Database\Seeders;

use App\Enums\NewsletterCampaignStatus;
use App\Models\NewsletterCampaign;
use Illuminate\Database\Seeder;

class NewsletterCampaignSeeder extends Seeder
{
    public function run(): void
    {
        NewsletterCampaign::updateOrCreate(
            ['name' => 'Summer 2026 — example draft'],
            [
                'subject' => 'Summer jump days just dropped ☀️',
                'preheader' => 'Fresh tandem dates and a Seville AFF course — book before they go.',
                'status' => NewsletterCampaignStatus::Draft,
                'blocks' => [
                    ['type' => 'heading', 'data' => ['text' => 'The skies are open', 'level' => 'h1']],
                    ['type' => 'paragraph', 'data' => ['text' => '<p>We’ve added a fresh batch of tandem dates through the summer and a brand-new AFF course in Seville. Whether it’s your first jump or your A-licence, now’s the time.</p>']],
                    ['type' => 'image', 'data' => ['image' => '/images/hero-skydive.jpg', 'caption' => 'Freefall over the dropzone', 'link' => '']],
                    ['type' => 'button', 'data' => ['label' => 'Book a tandem', 'url' => '/tandem']],
                    ['type' => 'divider', 'data' => []],
                    ['type' => 'two_column', 'data' => ['image' => '/images/aff.jpg', 'heading' => 'Go all the way', 'text' => 'Our AFF course takes you from first jump to a licence in eight levels.', 'button_label' => 'See AFF', 'button_url' => '/aff', 'image_side' => 'left']],
                    ['type' => 'latest_news', 'data' => []],
                ],
            ],
        );
    }
}
