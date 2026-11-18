<?php

namespace Tests\Feature\Services\Pixelforge;

use App\Services\Pixelforge\Exceptions\ConcurrencyLimitExceededException;
use App\Services\Pixelforge\Exceptions\ContentPolicyViolationException;
use App\Services\Pixelforge\Exceptions\DailyLimitExceededException;
use App\Services\Pixelforge\Exceptions\IdempotencyKeyReusedException;
use App\Services\Pixelforge\Exceptions\InvalidApiKeyException;
use App\Services\Pixelforge\Exceptions\NotFoundException;
use App\Services\Pixelforge\Exceptions\OverloadedException;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\Exceptions\RateLimitExceededException;
use App\Services\Pixelforge\Exceptions\RetryableException;
use App\Services\Pixelforge\Exceptions\ValidationFailedException;
use App\Services\Pixelforge\PixelforgeClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test strategy: every documented error `code` maps to its own exception class, so callers
 * branch on types, never on strings. Each case fakes the documented status, body and headers
 * and asserts the class, the carried data (status, Retry-After, rate-limit headers, field errors)
 * and whether it is safe to retry. Unknown codes, non-JSON bodies and network failures are covered too.
 * Error body shape: {"error": {"type", "code", "message", "param"?, "errors"?}}.
 */
class PixelforgeErrorMappingTest extends TestCase
{
    private const string GENERATION_URL = 'https://pixelforge.test/v1/generations/gen_123';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.pixelforge.base_url' => 'https://pixelforge.test',
            'services.pixelforge.api_key' => 'pf_test_key',
        ]);
    }

    /**
     * @return array<string, array{string, string, int, class-string<PixelforgeException>, bool}>
     */
    public static function documentedErrors(): array
    {
        return [
            'invalid api key' => ['invalid_api_key', 'authentication_error', 401, InvalidApiKeyException::class, false],
            'validation failed' => ['validation_failed', 'invalid_request_error', 422, ValidationFailedException::class, false],
            'content policy' => ['content_policy_violation', 'invalid_request_error', 400, ContentPolicyViolationException::class, false],
            'not found' => ['not_found', 'invalid_request_error', 404, NotFoundException::class, false],
            'idempotency key reused' => ['idempotency_key_reused', 'idempotency_error', 409, IdempotencyKeyReusedException::class, false],
            'rate limit' => ['rate_limit_exceeded', 'rate_limit_error', 429, RateLimitExceededException::class, true],
            'concurrency limit' => ['concurrency_limit_exceeded', 'rate_limit_error', 429, ConcurrencyLimitExceededException::class, true],
            'daily limit' => ['daily_limit_exceeded', 'rate_limit_error', 429, DailyLimitExceededException::class, false],
            'overloaded' => ['overloaded', 'api_error', 503, OverloadedException::class, true],
        ];
    }

    /**
     * @param  class-string<PixelforgeException>  $expectedClass
     */
    #[DataProvider('documentedErrors')]
    public function test_each_error_code_maps_to_its_exception(
        string $code, string $type, int $status, string $expectedClass, bool $retryable,
    ): void {
        $this->fakeError(['type' => $type, 'code' => $code, 'message' => 'Provider says no.'], $status);

        $exception = $this->captureException();

        $this->assertInstanceOf($expectedClass, $exception);
        $this->assertSame($code, $exception->errorCode);
        $this->assertSame($type, $exception->errorType);
        $this->assertSame($status, $exception->httpStatus);
        $this->assertSame('Provider says no.', $exception->getMessage());
        $this->assertSame($retryable, $exception instanceof RetryableException);
    }

    public function test_validation_errors_carry_the_field_and_all_messages(): void
    {
        $this->fakeError([
            'type' => 'invalid_request_error',
            'code' => 'validation_failed',
            'message' => 'The selected size is invalid.',
            'param' => 'size',
            'errors' => ['size' => ['The size must be one of: 1024x1024, 1536x1024, 1024x1536.']],
        ], 422);

        $exception = $this->captureException();

        $this->assertInstanceOf(ValidationFailedException::class, $exception);
        $this->assertSame('size', $exception->param);
        $this->assertSame(['size' => ['The size must be one of: 1024x1024, 1536x1024, 1024x1536.']], $exception->errors);
    }

    public function test_retryable_errors_carry_retry_after_and_rate_limit_headers(): void
    {
        $this->fakeError(['type' => 'rate_limit_error', 'code' => 'concurrency_limit_exceeded', 'message' => 'Busy.'], 429, [
            'Retry-After' => '5', 'X-RateLimit-Limit' => '60', 'X-RateLimit-Remaining' => '0',
        ]);

        $exception = $this->captureException();

        $this->assertInstanceOf(ConcurrencyLimitExceededException::class, $exception);
        $this->assertSame(5, $exception->retryAfterSeconds);
        $this->assertSame(60, $exception->rateLimitLimit);
        $this->assertSame(0, $exception->rateLimitRemaining);
    }

    public function test_a_missing_or_malformed_retry_after_is_null(): void
    {
        $this->fakeError(['type' => 'api_error', 'code' => 'overloaded', 'message' => 'Busy.'], 503, ['Retry-After' => 'soon']);

        $this->assertNull($this->captureException()->retryAfterSeconds);
    }

    public function test_an_unknown_error_code_becomes_a_generic_exception(): void
    {
        $this->fakeError(['type' => 'invalid_request_error', 'code' => 'date_range_too_long', 'message' => 'Too long.'], 422);

        $exception = $this->captureException();

        $this->assertSame(PixelforgeException::class, $exception::class);
        $this->assertSame('date_range_too_long', $exception->errorCode);
    }

    public function test_a_non_json_server_error_becomes_a_generic_exception(): void
    {
        Http::fake([self::GENERATION_URL => Http::response('<html>Bad gateway</html>', 502)]);

        $exception = $this->captureException();

        $this->assertSame(PixelforgeException::class, $exception::class);
        $this->assertSame('unexpected_response', $exception->errorCode);
        $this->assertSame(502, $exception->httpStatus);
    }

    public function test_a_network_failure_is_retryable(): void
    {
        Http::fake([self::GENERATION_URL => Http::failedConnection()]);

        $exception = $this->captureException();

        $this->assertInstanceOf(PixelforgeConnectionException::class, $exception);
        $this->assertInstanceOf(RetryableException::class, $exception);
        $this->assertNull($exception->httpStatus);
    }

    /**
     * @param  array<string, mixed>  $error
     * @param  array<string, string>  $headers
     */
    private function fakeError(array $error, int $status, array $headers = []): void
    {
        Http::fake([self::GENERATION_URL => Http::response(['error' => $error], $status, $headers)]);
    }

    private function captureException(): PixelforgeException
    {
        try {
            $this->app->make(PixelforgeClient::class)->generation('gen_123');
        } catch (PixelforgeException $exception) {
            return $exception;
        }

        $this->fail('Expected a Pixelforge exception.');
    }
}
