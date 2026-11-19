<?php

namespace Database\Factories;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostBaseline>
 */
class CostBaselineFactory extends Factory
{
    /**
     * A baseline with no samples yet, at the seed range.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'size' => fake()->randomElement(ImageSize::cases()),
            'quality' => fake()->randomElement(ImageQuality::cases()),
            ...CostBaseline::seedRange(),
        ];
    }

    /**
     * A baseline narrowed by recorded generations.
     */
    public function sampled(int $minCents, int $maxCents, int $sampleCount): static
    {
        return $this->state(fn (): array => [
            'min_cents' => $minCents,
            'max_cents' => $maxCents,
            'sample_count' => $sampleCount,
        ]);
    }
}
