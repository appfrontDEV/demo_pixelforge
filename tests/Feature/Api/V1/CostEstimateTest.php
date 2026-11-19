<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use App\Models\User;
use Database\Seeders\CostBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test strategy: GET /api/v1/cost-estimate (documented in docs/openapi.yaml).
 * Data: the seeded 9 baselines; 1024x1024 / high overwritten by the factory with 12 – 47 cents
 * from 4 samples; a Sanctum-authenticated factory user.
 * - Returns {data: {size, quality, min_cents, max_cents, sample_count}} for the chosen options,
 *   and the seed range for combinations with no samples yet.
 * - Missing or unknown size / quality give 422 {message, errors} on that field.
 * - Unauthenticated calls get a JSON 401.
 */
class CostEstimateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CostBaselineSeeder::class);
        CostBaseline::forOptions(ImageSize::Square, ImageQuality::High)
            ->fill(['min_cents' => 12, 'max_cents' => 47, 'sample_count' => 4])
            ->save();
    }

    public function test_it_returns_the_recorded_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.cost-estimate', ['size' => '1024x1024', 'quality' => 'high']))
            ->assertOk()
            ->assertExactJson(['data' => [
                'size' => '1024x1024',
                'quality' => 'high',
                'min_cents' => 12,
                'max_cents' => 47,
                'sample_count' => 4,
            ]]);
    }

    public function test_it_returns_the_seed_range_without_samples(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.cost-estimate', ['size' => '1536x1024', 'quality' => 'low']))
            ->assertOk()
            ->assertJsonPath('data.min_cents', 0)
            ->assertJsonPath('data.max_cents', 100_000)
            ->assertJsonPath('data.sample_count', 0);
    }

    public function test_missing_and_unknown_options_are_validation_errors(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.cost-estimate'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size', 'quality']);

        $this->getJson(route('api.v1.cost-estimate', ['size' => '512x512', 'quality' => 'ultra']))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['size', 'quality']]);
    }

    public function test_unauthenticated_requests_get_json_401(): void
    {
        $this->getJson(route('api.v1.cost-estimate', ['size' => '1024x1024', 'quality' => 'high']))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }
}
