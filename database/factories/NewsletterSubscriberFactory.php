<?php

namespace Database\Factories;

use App\Enums\NewsletterStatus;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'status' => NewsletterStatus::Confirmed,
            'source' => 'footer',
            'consented_at' => now(),
            'confirmed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => NewsletterStatus::Pending,
            'confirmed_at' => null,
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (): array => [
            'status' => NewsletterStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);
    }
}
