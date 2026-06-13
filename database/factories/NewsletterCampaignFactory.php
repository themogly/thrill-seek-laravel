<?php

namespace Database\Factories;

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
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraphs(3, true),
            'recipient_count' => 0,
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'sent_at' => now(),
            'recipient_count' => fake()->numberBetween(1, 200),
        ]);
    }
}
