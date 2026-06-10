<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'original_filename' => str_replace(' ', '-', $name).'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(50_000, 2_000_000),
        ];
    }
}
