<?php

namespace Tests\Feature\Services\Pixelforge;

use App\Services\Pixelforge\Exceptions\InvalidApiKeyException;
use App\Services\Pixelforge\Exceptions\OverloadedException;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\PixelforgeClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test strategy: the client is the only place that talks to Pixelforge. Every test fakes HTTP
 * (stray requests fail) and checks what was sent as well as what came back.
 * - Configuration comes from config('services.pixelforge.*'), one key per environment.
 * - GET /v1/pricing needs no auth, is cached as a plain array, and failures are not cached.
 * - Authenticated calls send the bearer key and refuse to run without one.
 * - Creating a generation sends the Idempotency-Key and requires an `id` in the response.
 * Fixture: the pricing payload documented at /docs#pricing.
 */
class PixelforgeClientTest extends TestCase
{
    private const string BASE_URL = 'https://pixelforge.test';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.pixelforge.base_url' => self::BASE_URL,
            'services.pixelforge.api_key' => 'pf_test_key',
        ]);
    }

    public function test_it_fetches_pricing_without_authentication(): void
    {
        Http::fake([self::BASE_URL.'/v1/pricing' => Http::response(self::pricingPayload())]);

        $pricing = $this->client()->pricing();

        $this->assertSame('12.50', $pricing['rates']['reasoning_tokens']);
        $this->assertSame(6450, $pricing['tokens']['output']['high']['1536x1024']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_it_caches_pricing_as_an_array(): void
    {
        Http::fake([self::BASE_URL.'/v1/pricing' => Http::response(self::pricingPayload())]);

        $first = $this->client()->pricing();
        $second = $this->client()->pricing();

        $this->assertSame($first, $second);
        $this->assertIsArray(Cache::get(PixelforgeClient::PRICING_CACHE_KEY));
        Http::assertSentCount(1);
    }

    public function test_it_does_not_cache_a_failed_pricing_request(): void
    {
        Http::fake([self::BASE_URL.'/v1/pricing' => Http::sequence()
            ->push(self::errorBody('overloaded', 'api_error'), 503, ['Retry-After' => '2'])
            ->push(self::pricingPayload())]);

        try {
            $this->client()->pricing();
            $this->fail('Expected the overloaded error to be thrown.');
        } catch (OverloadedException) {
            $this->assertFalse(Cache::has(PixelforgeClient::PRICING_CACHE_KEY));
        }

        $this->assertSame('40.00', $this->client()->pricing()['rates']['output_tokens']);
    }

    public function test_it_rejects_a_pricing_payload_without_rates(): void
    {
        Http::fake([self::BASE_URL.'/v1/pricing' => Http::response(['object' => 'pricing'])]);

        $this->expectException(PixelforgeException::class);
        $this->expectExceptionMessage('pricing');

        $this->client()->pricing();
    }

    public function test_it_fetches_a_generation_with_the_bearer_key(): void
    {
        Http::fake([self::BASE_URL.'/v1/generations/gen_123' => Http::response([
            'id' => 'gen_123', 'object' => 'generation', 'status' => 'processing',
        ])]);

        $generation = $this->client()->generation('gen_123');

        $this->assertSame('processing', $generation['status']);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer pf_test_key')
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_it_creates_a_generation_with_an_idempotency_key(): void
    {
        Http::fake([self::BASE_URL.'/v1/generations' => Http::response(['id' => 'gen_123', 'status' => 'queued'], 202)]);

        $generation = $this->client()->createGeneration(['prompt' => 'A fox'], 'local-uuid');

        $this->assertSame('gen_123', $generation['id']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Idempotency-Key', 'local-uuid')
            && $request->hasHeader('Authorization', 'Bearer pf_test_key')
            && $request->data() === ['prompt' => 'A fox']);
    }

    public function test_it_rejects_a_created_generation_without_an_id(): void
    {
        Http::fake([self::BASE_URL.'/v1/generations' => Http::response(['status' => 'queued'], 202)]);

        try {
            $this->client()->createGeneration(['prompt' => 'A fox'], 'local-uuid');
            $this->fail('Expected a generation without an id to be rejected.');
        } catch (PixelforgeException $exception) {
            $this->assertSame('unexpected_response', $exception->errorCode);
        }
    }

    public function test_it_refuses_authenticated_calls_without_an_api_key(): void
    {
        config(['services.pixelforge.api_key' => null]);
        Http::fake();

        try {
            $this->client()->generation('gen_123');
            $this->fail('Expected a missing key to be rejected.');
        } catch (InvalidApiKeyException $exception) {
            $this->assertSame('invalid_api_key', $exception->errorCode);
        }

        Http::assertNothingSent();
    }

    private function client(): PixelforgeClient
    {
        return $this->app->make(PixelforgeClient::class);
    }

    /**
     * The documented GET /v1/pricing response.
     *
     * @return array<string, mixed>
     */
    private static function pricingPayload(): array
    {
        return [
            'object' => 'pricing',
            'currency' => 'usd',
            'unit' => 'per_1m_tokens',
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'tokens' => [
                'characters_per_text_token' => 4,
                'text_token_variance' => 0.15,
                'per_reference_image' => 765,
                'output' => [
                    'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                    'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                    'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
                ],
                'reasoning' => [
                    'low' => ['min' => 0, 'max' => 500],
                    'medium' => ['min' => 200, 'max' => 2000],
                    'high' => ['min' => 1000, 'max' => 8000],
                ],
            ],
        ];
    }

    /**
     * @return array{error: array{type: string, code: string, message: string}}
     */
    private static function errorBody(string $code, string $type): array
    {
        return ['error' => ['type' => $type, 'code' => $code, 'message' => "Error {$code}."]];
    }
}
