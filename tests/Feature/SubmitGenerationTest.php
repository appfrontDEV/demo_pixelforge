<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Services\Pixelforge\Exceptions\OverloadedException;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\PixelforgeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Test strategy: SubmitGeneration, the queued job that sends a pending generation to Pixelforge.
 * Every test fakes HTTP (stray requests fail).
 * Data: a pending factory generation (1536x1024 / high, prompt "A lighthouse at dawn"); the
 * documented 202 create response (generation object in `queued`, with `rates`); documented
 * error bodies {"error": {type, code, message, param?, errors?}}.
 * - The POST carries the bearer key, `Idempotency-Key` = local UUID, `metadata.local_id` = local
 *   UUID and only documented parameters (no `reference_images` key when there are none).
 * - Accepted: stores provider_id and rates and moves to queued (submitted_at set).
 * - Idempotent replay: a lost first attempt is retried with the same key; the replayed 202
 *   (`Idempotent-Replayed: true`) fills the same row, nothing is duplicated, and a later run of
 *   the job sends nothing. A replay reporting `succeeded` never makes the row final here: the
 *   final status and cost come from the webhook (#13) or the reconcile job (#14).
 * - Permanent errors (422 validation, 400 content policy) fail the row at $0 with the code,
 *   message and per-field errors. Retryable errors (503, network, 5xx) are rethrown so the
 *   queue retries, leaving the row pending; when retries run out, failed() fails the row.
 */
class SubmitGenerationTest extends TestCase
{
    use RefreshDatabase;

    private const string BASE_URL = 'https://pixelforge.test';

    private const string CREATE_URL = self::BASE_URL.'/v1/generations';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.pixelforge.base_url' => self::BASE_URL,
            'services.pixelforge.api_key' => 'pf_test_key',
        ]);
    }

    public function test_it_sends_the_idempotency_key_metadata_and_documented_parameters(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::response(self::accepted(), 202)]);

        $this->runJob($generation);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::CREATE_URL
            && $request->hasHeader('Authorization', 'Bearer pf_test_key')
            && $request->hasHeader('Idempotency-Key', $generation->id)
            && $request->data() === [
                'prompt' => 'A lighthouse at dawn',
                'size' => '1536x1024',
                'quality' => 'high',
                'metadata' => ['local_id' => $generation->id],
            ]);
    }

    public function test_reference_images_are_sent_when_present(): void
    {
        $generation = $this->pendingGeneration(['reference_images' => ['https://example.com/style.png']]);
        Http::fake([self::CREATE_URL => Http::response(self::accepted(), 202)]);

        $this->runJob($generation);

        Http::assertSent(fn (Request $request): bool => $request['reference_images'] === ['https://example.com/style.png']);
    }

    public function test_an_accepted_generation_stores_the_provider_id_rates_and_queued_status(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::response(self::accepted(), 202)]);

        $this->runJob($generation);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Queued, $generation->status);
        $this->assertSame('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K', $generation->provider_id);
        $this->assertSame(self::accepted()['rates'], $generation->rates);
        $this->assertNotNull($generation->submitted_at);
    }

    public function test_a_lost_attempt_is_replayed_without_duplicating(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::sequence()
            ->pushFailedConnection()
            ->push(self::accepted(), 202, ['Idempotent-Replayed' => 'true']),
        ]);

        $this->assertJobThrows(PixelforgeConnectionException::class, $generation);
        $this->assertSame(GenerationStatus::Pending, $generation->fresh()?->status);

        $this->runJob($generation);
        $this->runJob($generation);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', $generation->id));
        $this->assertSame(1, Generation::count());
        $this->assertSame(GenerationStatus::Queued, $generation->fresh()?->status);
        $this->assertSame('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K', $generation->fresh()?->provider_id);
    }

    public function test_a_replay_reporting_a_final_status_does_not_finish_the_row(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::response(
            ['status' => 'succeeded'] + self::accepted(),
            202,
            ['Idempotent-Replayed' => 'true'],
        )]);

        $this->runJob($generation);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Processing, $generation->status);
        $this->assertNull($generation->price_nusd);
        $this->assertSame('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K', $generation->provider_id);
    }

    public function test_a_provider_validation_error_fails_the_row_with_its_fields(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::response(['error' => [
            'type' => 'invalid_request_error',
            'code' => 'validation_failed',
            'message' => 'The selected size is invalid.',
            'param' => 'size',
            'errors' => ['size' => ['The size must be one of: 1024x1024, 1536x1024, 1024x1536.']],
        ]], 422)]);

        $this->runJob($generation);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('validation_failed', $generation->error_code);
        $this->assertSame('The selected size is invalid.', $generation->error_message);
        $this->assertSame(['size' => ['The size must be one of: 1024x1024, 1536x1024, 1024x1536.']], $generation->error_fields);
        $this->assertTrue($generation->price_nusd?->isZero());
        $this->assertNotNull($generation->completed_at);
    }

    public function test_a_content_policy_rejection_fails_the_row_for_free(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::response(['error' => [
            'type' => 'invalid_request_error',
            'code' => 'content_policy_violation',
            'message' => 'The prompt was rejected.',
        ]], 400)]);

        $this->runJob($generation);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('content_policy_violation', $generation->error_code);
        $this->assertNull($generation->error_fields);
        $this->assertTrue($generation->provider_cost_nusd?->isZero());
    }

    public function test_retryable_errors_are_rethrown_and_leave_the_row_pending(): void
    {
        $generation = $this->pendingGeneration();
        Http::fake([self::CREATE_URL => Http::sequence()
            ->push(['error' => ['type' => 'api_error', 'code' => 'overloaded', 'message' => 'Overloaded.']], 503, ['Retry-After' => '2'])
            ->push(['error' => ['type' => 'api_error', 'code' => 'internal_error', 'message' => 'Boom.']], 500),
        ]);

        $this->assertJobThrows(OverloadedException::class, $generation);
        $this->assertJobThrows(PixelforgeException::class, $generation);

        $this->assertSame(GenerationStatus::Pending, $generation->fresh()?->status);
    }

    public function test_running_out_of_retries_fails_the_row(): void
    {
        $generation = $this->pendingGeneration();

        (new SubmitGeneration($generation))->failed(new OverloadedException('Overloaded.', 'overloaded'));

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('overloaded', $generation->error_code);
        $this->assertTrue($generation->price_nusd?->isZero());
    }

    public function test_an_unexpected_exception_fails_the_row_with_a_generic_code(): void
    {
        $generation = $this->pendingGeneration();

        (new SubmitGeneration($generation))->failed(new RuntimeException('Disk full.'));

        $this->assertSame('submission_failed', $generation->fresh()?->error_code);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function pendingGeneration(array $attributes = []): Generation
    {
        return Generation::factory()->create([
            'prompt' => 'A lighthouse at dawn',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            ...$attributes,
        ]);
    }

    private function runJob(Generation $generation): void
    {
        (new SubmitGeneration($generation->fresh() ?? $generation))->handle(app(PixelforgeClient::class));
    }

    /**
     * @param  class-string<\Throwable>  $exceptionClass
     */
    private function assertJobThrows(string $exceptionClass, Generation $generation): void
    {
        try {
            $this->runJob($generation);
        } catch (\Throwable $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);

            return;
        }

        $this->fail("Expected {$exceptionClass}.");
    }

    /**
     * The documented 202 body of POST /v1/generations: the generation object, queued.
     *
     * @return array<string, mixed>
     */
    private static function accepted(): array
    {
        return [
            'id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
            'object' => 'generation',
            'status' => 'queued',
            'prompt' => 'A lighthouse at dawn',
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => [],
            'metadata' => ['local_id' => 'ignored-in-assertions'],
            'image_url' => null,
            'image_url_expires_at' => null,
            'usage' => null,
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'error' => null,
            'created_at' => '2026-11-19T14:03:11Z',
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
