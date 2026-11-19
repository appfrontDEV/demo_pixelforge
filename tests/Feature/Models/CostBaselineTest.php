<?php

namespace Tests\Feature\Models;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use App\Money\Cents;
use Database\Seeders\CostBaselineSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test strategy: the cost_baselines table, one row per size × quality.
 * Data: the CostBaselineSeeder (9 rows at the seed range 0 – 100000 cents) and factory rows.
 * - The seeder creates all 9 combinations at the seed range with no samples, and running it
 *   again neither duplicates rows nor resets recorded ones (idempotent).
 * - forOptions() returns the stored row, or an unsaved seed-range row when none exists yet.
 * - size + quality is unique; casts give enums and Cents.
 */
class CostBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_every_combination_at_the_seed_range(): void
    {
        $this->seed(CostBaselineSeeder::class);

        $this->assertSame(9, CostBaseline::count());
        CostBaseline::all()->each(function (CostBaseline $baseline): void {
            $this->assertTrue($baseline->min_cents->equals(Cents::fromCents(CostBaseline::SEED_MIN_CENTS)));
            $this->assertTrue($baseline->max_cents->equals(Cents::fromCents(CostBaseline::SEED_MAX_CENTS)));
            $this->assertSame(0, $baseline->sample_count);
        });
    }

    public function test_seeder_is_idempotent_and_keeps_recorded_rows(): void
    {
        CostBaseline::factory()->sampled(120, 450, 3)->create([
            'size' => ImageSize::Square,
            'quality' => ImageQuality::High,
        ]);

        $this->seed(CostBaselineSeeder::class);
        $this->seed(CostBaselineSeeder::class);

        $this->assertSame(9, CostBaseline::count());
        $recorded = CostBaseline::forOptions(ImageSize::Square, ImageQuality::High);
        $this->assertSame([120, 450, 3], [$recorded->min_cents->cents, $recorded->max_cents->cents, $recorded->sample_count]);
    }

    public function test_for_options_falls_back_to_an_unsaved_seed_row(): void
    {
        $baseline = CostBaseline::forOptions(ImageSize::Portrait, ImageQuality::Low);

        $this->assertFalse($baseline->exists);
        $this->assertSame(ImageSize::Portrait, $baseline->size);
        $this->assertSame(ImageQuality::Low, $baseline->quality);
        $this->assertSame([0, 100_000, 0], [$baseline->min_cents->cents, $baseline->max_cents->cents, $baseline->sample_count]);
    }

    public function test_size_and_quality_are_unique_together(): void
    {
        CostBaseline::factory()->create(['size' => ImageSize::Square, 'quality' => ImageQuality::Low]);

        $this->expectException(QueryException::class);

        CostBaseline::factory()->create(['size' => ImageSize::Square, 'quality' => ImageQuality::Low]);
    }
}
