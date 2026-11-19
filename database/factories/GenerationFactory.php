<?php

namespace Database\Factories;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * A freshly created, not yet submitted generation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'prompt' => fake()->sentence(),
            'size' => fake()->randomElement(['1024x1024', '1536x1024', '1024x1536']),
            'quality' => fake()->randomElement(['low', 'medium', 'high']),
            'reference_images' => [],
            'status' => GenerationStatus::Pending,
            'estimate_min_cents' => 0,
            'estimate_max_cents' => 100_000,
        ];
    }

    /**
     * Accepted by Pixelforge.
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Queued,
            'provider_id' => 'gen_'.Str::lower(Str::random(24)),
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'submitted_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->queued()->state(fn (array $attributes) => [
            'status' => GenerationStatus::Processing,
            'started_at' => now(),
        ]);
    }

    /**
     * The docs' worked maximum: $0.373875000 provider cost, $0.467343750 exact customer price.
     */
    public function succeeded(): static
    {
        return $this->processing()->state(fn (array $attributes) => [
            'status' => GenerationStatus::Succeeded,
            'usage' => [
                'input_text_tokens' => 115,
                'input_image_tokens' => 1_530,
                'output_tokens' => 6_450,
                'reasoning_tokens' => 8_000,
                'total_tokens' => 16_095,
            ],
            'provider_cost_nusd' => 373_875_000,
            'price_nusd' => 467_343_750,
            'image_url' => fake()->imageUrl(),
            'image_url_expires_at' => now()->addHour(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Failed after acceptance. Failures are free.
     */
    public function failed(): static
    {
        return $this->processing()->state(fn (array $attributes) => [
            'status' => GenerationStatus::Failed,
            'provider_cost_nusd' => 0,
            'price_nusd' => 0,
            'error_code' => 'generation_failed',
            'error_message' => 'The generation failed.',
            'completed_at' => now(),
        ]);
    }
}
