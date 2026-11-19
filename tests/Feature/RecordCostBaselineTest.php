<?php

namespace Tests\Feature;

use App\Actions\RecordCostBaseline;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use App\Models\Generation;
use App\Money\NanoUsd;
use Database\Seeders\CostBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test strategy: RecordCostBaseline, which learns the estimate range from real prices.
 * Data: the seeded 9 baselines; succeeded generations (factory) for 1024x1024 / high with chosen
 * exact prices in nano-USD, e.g. 467_343_750 (the documented max, $0.467…) rounds up to 47 cents.
 * - The first sample replaces the seed range with [price, price]; later samples only widen it.
 * - The price is rounded UP to whole cents (a sub-cent price counts as 1 cent).
 * - Other size × quality rows are untouched.
 * - Failed and not-yet-final generations are not recorded (nothing changes, returns false).
 * - A missing baseline row is created from the seed range before the sample is applied.
 */
class RecordCostBaselineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CostBaselineSeeder::class);
    }

    public function test_first_sample_replaces_the_seed_range(): void
    {
        $this->assertTrue($this->record(467_343_750));

        $this->assertBaseline(47, 47, 1);
    }

    public function test_later_samples_widen_min_and_max(): void
    {
        $this->record(300_000_000);
        $this->record(467_343_750);
        $this->record(120_000_000);
        $this->record(200_000_000);

        $this->assertBaseline(12, 47, 4);
    }

    public function test_a_sub_cent_price_is_rounded_up_to_one_cent(): void
    {
        $this->record(5_000);

        $this->assertBaseline(1, 1, 1);
    }

    public function test_other_combinations_are_untouched(): void
    {
        $this->record(467_343_750);

        $untouched = CostBaseline::query()->where('sample_count', 0)->get();
        $this->assertCount(8, $untouched);
        $this->assertTrue($untouched->every(fn (CostBaseline $baseline): bool => $baseline->min_cents->cents === 0
            && $baseline->max_cents->cents === 100_000));
    }

    public function test_failed_and_unfinished_generations_are_not_recorded(): void
    {
        $recorder = app(RecordCostBaseline::class);

        foreach ([Generation::factory()->failed(), Generation::factory()->processing()] as $factory) {
            $generation = $factory->create(['size' => ImageSize::Square, 'quality' => ImageQuality::High]);
            $this->assertFalse($recorder->handle($generation));
        }

        $this->assertBaseline(0, 100_000, 0);
    }

    public function test_a_missing_baseline_row_is_created(): void
    {
        CostBaseline::query()->delete();

        $this->record(467_343_750);

        $this->assertSame(1, CostBaseline::count());
        $this->assertBaseline(47, 47, 1);
    }

    /**
     * Records a succeeded 1024x1024 / high generation with the given exact price.
     */
    private function record(int $priceNanos): bool
    {
        $generation = Generation::factory()->succeeded()->create([
            'size' => ImageSize::Square,
            'quality' => ImageQuality::High,
            'price_nusd' => NanoUsd::fromNanos($priceNanos),
        ]);

        return app(RecordCostBaseline::class)->handle($generation);
    }

    private function assertBaseline(int $minCents, int $maxCents, int $sampleCount): void
    {
        $baseline = CostBaseline::forOptions(ImageSize::Square, ImageQuality::High);

        $this->assertSame(
            [$minCents, $maxCents, $sampleCount],
            [$baseline->min_cents->cents, $baseline->max_cents->cents, $baseline->sample_count],
        );
    }
}
