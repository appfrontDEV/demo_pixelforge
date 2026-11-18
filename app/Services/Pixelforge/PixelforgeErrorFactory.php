<?php

namespace App\Services\Pixelforge;

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
use App\Services\Pixelforge\Exceptions\ValidationFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

/**
 * Turns failed Pixelforge responses into typed exceptions, keyed on the documented `error.code`.
 */
class PixelforgeErrorFactory
{
    /**
     * Documented error codes and the exception each one raises. Unknown codes fall back to the base class.
     *
     * @var array<string, class-string<PixelforgeException>>
     */
    private const array EXCEPTIONS = [
        'invalid_api_key' => InvalidApiKeyException::class,
        'validation_failed' => ValidationFailedException::class,
        'content_policy_violation' => ContentPolicyViolationException::class,
        'not_found' => NotFoundException::class,
        'idempotency_key_reused' => IdempotencyKeyReusedException::class,
        'rate_limit_exceeded' => RateLimitExceededException::class,
        'concurrency_limit_exceeded' => ConcurrencyLimitExceededException::class,
        'daily_limit_exceeded' => DailyLimitExceededException::class,
        'overloaded' => OverloadedException::class,
    ];

    /**
     * Input: a non-2xx response. Output: the matching exception carrying the error body and headers.
     */
    public function fromResponse(Response $response): PixelforgeException
    {
        $error = $response->json('error');

        if (! is_array($error) || ! is_string($error['code'] ?? null)) {
            return $this->unexpectedResponse($response, "Pixelforge returned HTTP {$response->status()}.");
        }

        $exceptionClass = self::EXCEPTIONS[$error['code']] ?? PixelforgeException::class;

        return new $exceptionClass(
            message: is_string($error['message'] ?? null) ? $error['message'] : 'Pixelforge request failed.',
            errorCode: $error['code'],
            errorType: is_string($error['type'] ?? null) ? $error['type'] : null,
            httpStatus: $response->status(),
            param: is_string($error['param'] ?? null) ? $error['param'] : null,
            errors: is_array($error['errors'] ?? null) ? $error['errors'] : [],
            retryAfterSeconds: $this->integerHeader($response, 'Retry-After'),
            rateLimitLimit: $this->integerHeader($response, 'X-RateLimit-Limit'),
            rateLimitRemaining: $this->integerHeader($response, 'X-RateLimit-Remaining'),
        );
    }

    /**
     * A response whose body is not what the API documents (non-JSON, wrong shape).
     */
    public function unexpectedResponse(Response $response, string $message): PixelforgeException
    {
        return new PixelforgeException($message, 'unexpected_response', httpStatus: $response->status());
    }

    /**
     * No response at all: DNS, connection refused or timeout.
     */
    public function fromConnectionError(ConnectionException $exception): PixelforgeConnectionException
    {
        return new PixelforgeConnectionException(
            'Could not reach Pixelforge: '.$exception->getMessage(),
            'connection_failed',
            previous: $exception,
        );
    }

    /**
     * A header holding a whole number of seconds or requests, or null when absent or malformed.
     */
    private function integerHeader(Response $response, string $name): ?int
    {
        $value = $response->header($name);

        return preg_match('/^\d+$/', $value) === 1 ? (int) $value : null;
    }
}
