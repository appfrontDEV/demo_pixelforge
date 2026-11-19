<?php

namespace Tests\Feature\Api\V1;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Jobs\SubmitGeneration;
use App\Models\CostBaseline;
use App\Models\Generation;
use App\Models\User;
use Database\Seeders\CostBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test strategy: POST /api/v1/generations (documented in docs/openapi.yaml).
 * Data: the seeded baselines with 1536x1024 / high at 12 – 47 cents; a Sanctum-authenticated
 * factory user; the queue is faked, so nothing reaches Pixelforge here (see SubmitGenerationTest).
 * - Valid input: 202 {data: generation}, a pending row owned by the caller carrying the baseline
 *   estimate, and one SubmitGeneration job for that row.
 * - size and quality default to 1024x1024 and medium, like the provider.
 * - Input mirroring the provider's rules is rejected with 422 on the right field, no row and
 *   no job: missing or >4000-char prompt, unknown size/quality, >4 or non-http(s) reference
 *   images, a non-array reference_images.
 * - Unauthenticated calls get a JSON 401.
 */
class GenerationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seed(CostBaselineSeeder::class);
        CostBaseline::forOptions(ImageSize::Landscape, ImageQuality::High)
            ->fill(['min_cents' => 12, 'max_cents' => 47, 'sample_count' => 4])
            ->save();
    }

    public function test_it_stores_a_pending_generation_and_queues_its_submission(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.generations.store'), [
            'prompt' => 'A lighthouse at dawn',
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => ['https://example.com/style.png'],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.prompt', 'A lighthouse at dawn')
            ->assertJsonPath('data.size', '1536x1024')
            ->assertJsonPath('data.quality', 'high')
            ->assertJsonPath('data.reference_images', ['https://example.com/style.png'])
            ->assertJsonPath('data.estimate_min_cents', 12)
            ->assertJsonPath('data.estimate_max_cents', 47)
            ->assertJsonPath('data.price_usd', null)
            ->assertJsonPath('data.error', null);

        $generation = Generation::query()->sole();
        $this->assertSame($response->json('data.id'), $generation->id);
        $this->assertTrue($generation->user?->is($user));
        $this->assertSame(GenerationStatus::Pending, $generation->status);
        Queue::assertPushed(SubmitGeneration::class, fn (SubmitGeneration $job): bool => $job->generation->is($generation));
        Queue::assertPushed(SubmitGeneration::class, 1);
    }

    public function test_size_and_quality_default_like_the_provider(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.generations.store'), ['prompt' => 'A lighthouse at dawn'])
            ->assertAccepted()
            ->assertJsonPath('data.size', '1024x1024')
            ->assertJsonPath('data.quality', 'medium')
            ->assertJsonPath('data.reference_images', [])
            ->assertJsonPath('data.estimate_max_cents', CostBaseline::SEED_MAX_CENTS);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidInput(): array
    {
        $image = 'https://example.com/a.png';

        return [
            'missing prompt' => [[], 'prompt'],
            'prompt over 4000 characters' => [['prompt' => Str::repeat('a', 4001)], 'prompt'],
            'unknown size' => [['prompt' => 'x', 'size' => '512x512'], 'size'],
            'unknown quality' => [['prompt' => 'x', 'quality' => 'ultra'], 'quality'],
            'more than 4 reference images' => [['prompt' => 'x', 'reference_images' => array_fill(0, 5, $image)], 'reference_images'],
            'reference images not a list' => [['prompt' => 'x', 'reference_images' => $image], 'reference_images'],
            'non-http reference image' => [['prompt' => 'x', 'reference_images' => ['ftp://example.com/a.png']], 'reference_images.0'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidInput')]
    public function test_invalid_input_is_rejected_on_the_right_field(array $payload, string $field): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.generations.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => [$field]]);

        $this->assertSame(0, Generation::count());
        Queue::assertNothingPushed();
    }

    public function test_a_prompt_of_exactly_4000_characters_is_accepted(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.generations.store'), ['prompt' => Str::repeat('a', 4000)])->assertAccepted();
    }

    public function test_unauthenticated_requests_get_json_401(): void
    {
        $this->postJson(route('api.v1.generations.store'), ['prompt' => 'x'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        Queue::assertNothingPushed();
    }
}
