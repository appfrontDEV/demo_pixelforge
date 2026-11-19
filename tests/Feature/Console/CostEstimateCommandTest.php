<?php

namespace Tests\Feature\Console;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use Database\Seeders\CostBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test strategy: the cost:estimate console command, the console side of the estimate.
 * Data: the seeded 9 baselines, 1024x1024 / high at 12 – 47 cents from 4 samples.
 * - Prints the range in dollars and the sample count for the chosen options.
 * - Unknown size or quality exits with failure.
 */
class CostEstimateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prints_the_range(): void
    {
        $this->seed(CostBaselineSeeder::class);
        CostBaseline::forOptions(ImageSize::Square, ImageQuality::High)
            ->fill(['min_cents' => 12, 'max_cents' => 47, 'sample_count' => 4])
            ->save();

        $this->artisan('cost:estimate', ['size' => '1024x1024', 'quality' => 'high'])
            ->expectsOutputToContain('1024x1024 · high: $0.12 – $0.47 (4 samples)')
            ->assertSuccessful();
    }

    public function test_unknown_options_fail(): void
    {
        $this->artisan('cost:estimate', ['size' => 'huge', 'quality' => 'high'])->assertFailed();
        $this->artisan('cost:estimate', ['size' => '1024x1024', 'quality' => 'ultra'])->assertFailed();
    }
}
