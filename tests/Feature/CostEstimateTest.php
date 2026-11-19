<?php

namespace Tests\Feature;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use App\Models\User;
use Database\Seeders\CostBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test strategy: GET /cost-estimate, the web (session) side of the estimate, fetched by the
 * prompt form while the user changes size and quality.
 * Data: the seeded 9 baselines, 1024x1024 / high at 12 – 47 cents from 4 samples; a verified
 * factory user.
 * - Returns the same JSON shape as the API endpoint.
 * - Unknown options give 422 JSON errors; guests are redirected to login.
 */
class CostEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_users_get_the_estimate(): void
    {
        $this->seed(CostBaselineSeeder::class);
        CostBaseline::forOptions(ImageSize::Square, ImageQuality::High)
            ->fill(['min_cents' => 12, 'max_cents' => 47, 'sample_count' => 4])
            ->save();

        $this->actingAs(User::factory()->create())
            ->getJson(route('cost-estimate', ['size' => '1024x1024', 'quality' => 'high']))
            ->assertOk()
            ->assertExactJson(['data' => [
                'size' => '1024x1024',
                'quality' => 'high',
                'min_cents' => 12,
                'max_cents' => 47,
                'sample_count' => 4,
            ]]);
    }

    public function test_unknown_options_are_validation_errors(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('cost-estimate', ['size' => 'huge', 'quality' => 'medium']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('cost-estimate', ['size' => '1024x1024', 'quality' => 'high']))
            ->assertRedirect(route('login'));
    }
}
