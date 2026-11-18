<?php

namespace App\Services\Pixelforge\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Any error returned by (or while reaching) the Pixelforge API.
 *
 * Mirrors the documented error body {"error": {type, code, message, param?, errors?}} plus the
 * response metadata callers need to react: HTTP status, Retry-After and the rate-limit headers.
 * Subclasses exist per documented `code`; unknown codes stay as this base class.
 */
class PixelforgeException extends RuntimeException
{
    /**
     * @param  string  $errorCode  Pixelforge `error.code`, e.g. "validation_failed"
     * @param  ?string  $errorType  Pixelforge `error.type`, e.g. "invalid_request_error"
     * @param  ?int  $httpStatus  null when no response was received
     * @param  ?string  $param  first invalid parameter (validation errors only)
     * @param  array<string, list<string>>  $errors  every problem per parameter (validation errors only)
     * @param  ?int  $retryAfterSeconds  value of the Retry-After header, when numeric
     * @param  ?int  $rateLimitLimit  X-RateLimit-Limit (requests per minute)
     * @param  ?int  $rateLimitRemaining  X-RateLimit-Remaining (requests left this minute)
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly ?string $errorType = null,
        public readonly ?int $httpStatus = null,
        public readonly ?string $param = null,
        public readonly array $errors = [],
        public readonly ?int $retryAfterSeconds = null,
        public readonly ?int $rateLimitLimit = null,
        public readonly ?int $rateLimitRemaining = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
