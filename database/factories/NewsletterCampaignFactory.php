<?php

namespace Database\Factories;

use App\Enums\NewsletterCampaignStatus;
use App\Models\NewsletterCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterCampaign>
 */
class NewsletterCampaignFactory extends Factory
{
    protected $model = NewsletterCampaign::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(5),
            'preheader' => fake()->sentence(8),
            'status' => NewsletterCampaignStatus::Draft,
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'Big skies ahead', 'level' => 'h1']],
                ['type' => 'paragraph', 'data' => ['text' => '<p>'.fake()->paragraph().'</p>']],
                ['type' => 'button', 'data' => ['label' => 'Book a jump', 'url' => 'https://example.test/tandem']],
            ],
            'recipient_count' => 0,
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => NewsletterCampaignStatus::Sent,
            'sent_at' => now(),
            'recipient_count' => fake()->numberBetween(1, 200),
        ]);
    }
}
