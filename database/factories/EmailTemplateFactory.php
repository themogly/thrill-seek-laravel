<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'key' => Str::slug($name, '_'),
            'name' => ucfirst($name),
            'subject' => fake()->sentence(4),
            'body' => "Hi {{ name }},\n\n".fake()->paragraph(),
            'variables' => ['name'],
        ];
    }
}
