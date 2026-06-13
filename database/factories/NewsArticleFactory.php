<?php

namespace Database\Factories;

use App\Models\NewsArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsArticle>
 */
class NewsArticleFactory extends Factory
{
    protected $model = NewsArticle::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'lead' => fake()->optional()->sentence(12),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'featured_image' => null,
            'published' => true,
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'byline' => 'By the G-Force team',
            'course_date_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['published' => false, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['published' => true, 'published_at' => now()->addWeek()]);
    }
}
