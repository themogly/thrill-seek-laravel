<?php

namespace Database\Factories;

use App\Models\UnmatchedInboundMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnmatchedInboundMessage>
 */
class UnmatchedInboundMessageFactory extends Factory
{
    protected $model = UnmatchedInboundMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'external_id' => 'email_'.fake()->unique()->bothify('??????????'),
            'from_email' => fake()->safeEmail(),
            'to_email' => 'enquiry+unknown@reply.gforce.test',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'reason' => 'unknown_token',
            'received_at' => now(),
        ];
    }
}
