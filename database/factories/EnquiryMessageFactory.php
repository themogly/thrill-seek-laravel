<?php

namespace Database\Factories;

use App\Enums\MessageDirection;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnquiryMessage>
 */
class EnquiryMessageFactory extends Factory
{
    protected $model = EnquiryMessage::class;

    public function definition(): array
    {
        return [
            'enquiry_id' => Enquiry::factory(),
            'direction' => MessageDirection::Inbound,
            'body' => fake()->paragraph(),
            'user_id' => null,
        ];
    }

    public function outbound(): static
    {
        return $this->state(['direction' => MessageDirection::Outbound]);
    }
}
